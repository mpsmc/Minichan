<?php
chdir('..');
$page_title = 'Update extension';

require 'includes/header.php';
if (!$administrator) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}
$xml = simplexml_load_file('chrome-extension/update.xml');
$currentVersion = (String) $xml->app->updatecheck->attributes()->version;
$chrome_extension_id = (String) $xml->app->attributes()->appid;

if ($_FILES['extension'] && $_FILES['extension']['name'] == 'minichan.crx') {
    $file = $_FILES['extension']['tmp_name'];
    $fh = fopen($file, 'r');
    $magicNumber = fread($fh, 4);
    $version = unpack('V', fread($fh, 4));
    $keyLength = unpack('V', fread($fh, 4));
    $signatureLength = unpack('V', fread($fh, 4));

    $version = $version[1];
    $keyLength = $keyLength[1];
    $signatureLength = $signatureLength[1];

    $key = fread($fh, $keyLength);
    $signature = fread($fh, $signatureLength);

    $extension_id = str_split(substr(hash('sha256', $key), 0, 32));
    foreach ($extension_id as $pos => $char) {
        if (is_numeric($char)) {
            $extension_id[$pos] = chr(ord($char) + 49);
        } else {
            $extension_id[$pos] = chr(ord($char) + 10);
        }
    }

    $extension_id = implode('', $extension_id);

    if ($extension_id != $chrome_extension_id) {
        add_error("Extension id didn't match! Did you sign using the corrent public key?");
    } else {
        $zipName = tempnam(sys_get_temp_dir(), '');
        $zipFile = fopen($zipName, 'w');
        do {
            $dataRead = fread($fh, 512);
            fwrite($zipFile, $dataRead);
        } while ($dataRead);
        fclose($zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipName)) {
            $newVersion = json_decode($zip->getFromName('manifest.json'))->version;
            $zip->close();
        } else {
            die('Could not open zip');
        }

        unlink($zipName);

        if (!$newVersion) {
            add_error('Something went horribly wrong!', true);
        }

        if (version_compare($newVersion, $currentVersion) != 1) {
            add_error('Trying to update to same/older version ('.$newVersion.'). Update manifest.json!');
        } else {
            unlink('chrome-extension/minichan.crx');
            move_uploaded_file($_FILES['extension']['tmp_name'], 'chrome-extension/minichan.crx');
            $xml->app->updatecheck->attributes()->version = $newVersion;
            $xml->app->updatecheck->attributes()->codebase = 'http://minichan.org/chrome-extension/minichan-'.$newVersion.'.crx';

            unlink('chrome-extension/update.xml');
            $xml->asXML('chrome-extension/update.xml');

            $currentVersion = $newVersion;
        }
    }
} elseif ($_FILES['extension']) {
    add_error('Uploaded file must be minichan.crx');
}

print_errors();
?>
Current version: <?php echo $currentVersion; ?><br>
Extension ID: <?php echo $chrome_extension_id; ?><br><br />
<form method="POST" enctype="multipart/form-data">
<input type="file" name="extension" /><input type="submit" value="Update" />
</form>
<?php
require 'includes/footer.php';
