<?php
// Main config file, make sure to edit and/or verify every single entry.
// Also don't forget to edit /includes/template.php to suit your needs.

// Database config.
$db_info = array
(
        'server' => 'localhost', // Usually "localhost". If you don't know, consult your host's FAQ.
        'username' => '', // Your database user/login name.
        'password' => '', // Your database password.
        'database' => '' // The name you chose for the database.
);

$spam_phrases = array();

define('GOOGLE_ANALYTICS_ID', '');
define('GOOGLE_ANALYTICS_DOMAIN', '');

//define('GOOGLE_TOKEN', ''); // Used for android notifications. You likely can't use it without a custom app
define('ENABLE_PROFILER', false);
define('ADMIN_PROFILER', true); // require administrator for profiler to activate
define('NODE_SECRET', ''); // Not really in use anymore. Can leave empty.
define('NODE_SERVER', ''); // Not really in use anymore. Can leave empty.
define('NODE_ERROR_RECIPIENT', 'admins');

define("POSTS_NEEDED_FOR_PROXY_RESTORE", 10);

// Main site configuration.
define("SITE_ROOT", realpath(dirname(__FILE__) . "/..")); // Do not change, things will break.
define('SITE_TITLE', ''); // The title of your site, shown in the main header among other places.

define('DOMAIN', ($_SERVER['HTTPS']=="on" ? "https" : "http") . '://localhost/'); // Your site's domain -- INCLUDE TRAILING SLASH!
define('STATIC_DOMAIN', ($_SERVER['HTTPS']=="on" ? "https" : "http") . "://localhost/");
define('SESSION_NAME', 'tinybbs');
define('COOKIE_DOMAIN', '');

define('ADMIN_NAME', 'Admin'); // This display's instead of "Anonymous *" when you reply as an admin, if set to anything else but Admin, be sure to edit topic.php and the admin page in the pages table in the db.
define('MAILER_ADDRESS', ''); // Your e-mail address. This will be used as the From: header for ID recovery e-mails.
define('SITE_FOUNDED', 0); // CHANGE ME! The Unix timestamp of your site's founding (used by statistics.php) You can find the current Unix timestamp at http://www.unixtimestamp.com/.
define('BREAK_OUT_FRAME', false); // Break out of frames, if needed.
define('ENABLE_NAMES', true); // Burp.

// Gold account shit. Lulz.
define("ENABLE_GOLD_ACCOUNTS", false);
define("PAYPAL_EMAIL", "");
define("GOLDACCOUNT_PRICE", "10.00");
define("GOLD_ACCOUNT_TIME", "31556926"); // In seconds. 31556926 = 1 year

// Bot detection
// Changing recaptcha to first post on every UID
define('ENABLE_RECAPTCHA_ON_BOT', true); // Enable this to detect bot-like behavoir (In development). Needs the following two set for your site.

define('RECAPTCHA_PUBLIC_KEY', '');  // // Public key. Get a key pair here: http://www.google.com/recaptcha (or whatever the proper link for recaptchav2 is)
define('RECAPTCHA_PRIVATE_KEY', ''); // Private key.

define('RECAPTCHA_NOTICE', '<b>Unfortunately you have exhibited bot like behaviour. Please fill in the following captcha to continue:</b>');
define('RECAPTCHA_MAX_SEARCHES_PER_MIN', 5);
define('RECAPTCHA_MIN_POSTS', 10);

// Errors and other messages.
define('MESSAGE_PAGE_NOT_FOUND', 'The page requested could not be located on the server.'); // Default page not found (404) message.
define('MESSAGE_PAGE_ACCESS_DENIED', 'User is not privileged. This incident will be reported.'); // Default access denied (403) message.
define('DEFCON_2_MESSAGE', 'Posting has been temporarly disabled for all users.'); // Notice displayed in DEFCON 2 mode.
define('DEFCON_3_MESSAGE', 'Posting has been temporarly disabled for non-regulars.'); // Notice displayed in DEFCON 3 mode.
define('DEFCON_4_MESSAGE', 'Creation of new accounts have been temporarly disabled. If you have an account you can restore it in the dashboard.'); // Notice displayed in DEFCON 4 mode.

// Images config.
define('ALLOW_IMAGES', true); // Allow image uploading?
define('ALLOW_IMGUR', true); // Allow imgur? Can be used with ALLOW_IMAGES false
define('IMGUR_CLIENT_ID', ''); // Client id, get one at https://api.imgur.com/oauth2/addclient the callback url does not matter.
define('MAX_IMAGE_SIZE', 560000); // Maximum image filesize in bytes.
define('MAX_IMAGE_DIMENSIONS', 180); // Maximum thumbnail height/width.
define('FANCY_IMAGE', true); // Use a fancy image viewer or not.
define('EMBED_VIDEOS', true); // Enable video (Youtube/Vimeo) embedding.

// Styles
define('DEFAULT_STYLESHEET', 'violet'); // Default style, don't include the .css. extention, use the name only.
define('AVAILABLE_STYLES', "blue;lime;mint;mono;pallet;pink;purple;sand;sim;turquoise;viridian;violet;yotsuba"); // List of avaiable styles seperated with semicolumns. Styles must be located in the style folder.

// Hiding of admins and/or moderator posts, can be quite useful to prevent drama.
define('HIDDEN_ADMINS', false); // Hides the hyperlink for admin posts.
define('HIDDEN_MODS', false); // Hides the hyperlink for mod posts.

// Miscellaneous stuff.
define('ENABLE_IRC_PING', false); // If enabled will ping a http server when posts/replies/modlog events happen
define('IRC_PING_DOMAIN', "");
define('IRC_PING_SECRET', '');

define('SALT', ''); // Just type some random stuff.
define('TRIPSEED', ''); // Just type some random stuff.
define('IP_INFO_SECRET', ''); // Just type some random stuff.
define('MOD_GZIP', false); // WARNING: ONLY TURN THIS ON IF YOUR HOST DOESNT SUPPORT DEFLATE/ZLIB.
define('ENABLE_CACHING', true); // Enable caching of stuff, to make stuff go faster and stuff.
define('MODERATOR_MONDAY', false); // Make everyone a mod. However they cannot ban, or use the profile page and related actions.

// Post limits.
define('ITEMS_PER_PAGE', 50); // The number of topics shown on the index, the number of replies on replies.php, etc.
define('MAX_LENGTH_HEADLINE', 100); // Maximum length of headlines.
define('MIN_LENGTH_HEADLINE', 3); // Minimum length of headlines.
define('MAX_LENGTH_BODY', 30000); // Maximum length of post bodies.
define('MIN_LENGTH_BODY', 3); // Minimum length of post bodies.
define('MAX_LINES', 150); // The maximum number of lines in a post body.

// Restrictions and waiting times.
define('REQUIRED_LURK_TIME_REPLY', 10); // How long should new IDs have to wait until they can reply?
define('REQUIRED_LURK_TIME_TOPIC', 10); // How long should new IDs have to wait until they can post a topic?
define('FLOOD_CONTROL_REPLY', 15); // Seconds an IP address must wait before posting another reply.
define('FLOOD_CONTROL_TOPIC', 60); // Seconds an IP address must wait before posting another topic.
define('ALLOW_MODS_EXTERMINATE', false); // Should moderators be allowed to use the dangerous exterminator tool?
define('NECRO_BUMP_TIME', 52 * 604800); // Topics older than this may not be bumped by non-mods. Fuckers.

// Flood control.
define('ALLOW_MODS_SEARCH_DISABLED', false); // Should mods be be allowed to toggle the search function?
define('POSTS_TO_DEFY_SEARCH_DISABLED', 5); // Replies a user needs to not be affected by search being disabled.
define('POSTS_TO_DEFY_DEFCON_3', 5); // Replies a user needs to not be affected by DEFCON 3.
define('ALLOW_MODS_DEFCON', true); // Should mods be allowed to set DEFCON modes. 1 & 2 are always restricted to admin use only.

// Post editing.
define('ALLOW_EDIT', true); // Should normal users be able to edit their posts?
define('TIME_TO_EDIT', 600); // How long in seconds should normal users have to edit their new posts?

// Bulletin settings.
define('ALLOW_USER_BULLETINS', true); // Allow bulletins being posted by normal users?
define('REQ_BULLETIN_POSTS', 10); // If users are allowed to post bulletins how many posts should they need?
define('TIME_BULLETINS', 600); // How many seconds between each bulletin for normal users.
define('PRE_MODERATE_BULLETINS', true); // Do we pre-moderate bulletins?

// Event settings.
define('ALLOW_USER_EVENTS', true); // Allow events being posted by normal users?
define('REQ_EVENT_POSTS', 10); // If users are allowed to post events how many posts should they need?
define('TIME_EVENTS', 600); // How many seconds between each bulletin for normal users.
define('PRE_MODERATE_EVENTS', true); // Do we pre-moderate bulletins?

// Poll settings:
define("ENABLE_POLLS", true);

// Not really used that much anymore. Administrators now hand out permissions through user profiles.
$moderators = array
(
                'name' => 'UID',
);

$administrators = array
(
                'name' => 'UID',
);

// Not really used that much anymore. Administrators now hand out permissions through user profiles.
$janitors = array
(
                'name' => 'UID',
);

// Usage:
// name###key => name !value
$vanity_trips = array
(
                
);
?>
