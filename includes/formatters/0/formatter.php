<?php
class LegacyFormatter implements MinichanFormatter {
	protected $html;
	protected $markup;

	public function __construct() {
		$this->markup = array ( 
			// 01 Bold.
			'/\[b\](.*?)\[\/b\]/is',
			// 02 Italic.
			'/\[i\](.*?)\[\/i\]/is',
			// 03 Spoiler.
			'/\[sp(?:oiler)?\](.*?)\[\/sp(?:oiler)?\]/is',
			// 04 Underline.
			'/\[u\](.*?)\[\/u\]/is',
			// 05 Strikethrough.
			'/\[s\](.*?)\[\/s\]/is',
			// 06 Linkify text in the form of [http://example.org text].
			'@\[(https?|ftps?)://([A-Z0-9/&#+%~=_+|?.,!:$;\-\@]+) (.+?)\]@i',
			// 07 Internal links.
			'/\[il\](.+?)\[\/il\]/',
			// 08 Quotes.
			'/^&gt;(.*)$/m',
			// 09 Headers.
			'/\[h\](.+?)\[\/h\]/m',
			// 10 Bordered text.
			'/\[border\](.+?)\[\/border\]/ms',
			// 11 Convert double dash in to a —.
	//		'/--/',
			// 12 Codebox.
			'/\[code\](.+?)\[\/code\]/ms',
			// 13 Highlights.
			'/\[hl\](.+?)\[\/hl\]/ms',
			// 14 Make some cool links.
			//'%\b(?<![\["\']|url=)((?:https?://|ftps?://|irc://|magnet://|magnet:\?)[A-Za-z0-9][^\s<]+)\b%i',
			'/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is',
			// 15 More url stuff
			//'%\b(?<![\["\'])(?<!http://|https://|url=)((?:www\.|ftp\.)[A-Za-z0-9][^\s<]+)\b%i',
			'/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is',
			// And now for the fucking idiots that wanted wiki syntax
			// 16. Strong emphasis.
			"/'''(.+?)'''/",
			// 17. Emphasis.
			"/''(.+?)''/",
			// 18. Headers.
			'/==(.+?)==/',
			// 19. Spoilers
			'/\*\*(.+?)\*\*/',
			// 20. Border
			'/\[\[(.+?)\]\]/',
			// 21. Strikethrough
			'/(?<!-)--(?!-)(.+?)(?<!-)--(?!-)/',
			// 22. Underline
			'/__(.+?)__/',
			// 23. Highlights
			'/\%\%(.+?)\%\%/',
			// 24. Proper links
			'/\[url=(?=https?:\/\/)([^\]]+)]([^\[]+)\[\/url]/i',
			// 25. Raised text.
			'/\[sup](.+?)\[\/sup]/ms',
			// 26. Facebook like
	//		'/(^|\W)fora(\W|$)/im',
			// 27. to4str
	//		'/(t04str|to4str|tο4str|toastr|toaster)/ims',
			// 28. W
			'/\[vv\]/',
			'/\[VV\]/',
		);
			
		$this->html = array (
			'<strong>$1</strong>', #01
			'<em>$1</em>', #02
			'<span class="spoiler">$1</span>', #03
			'<u>$1</u>', #04
			'<s>$1</s>', #05
			'<a href="$1://$2" title="$1://$2" rel="nofollow">$3</a>', #06
			'<em class="unimportant">(<a href="$1" rel="nofollow">topic $1</a>)</em>', #07
			'<span class="quote"><strong>></strong> $1</span>', #08
			'<h4 class="user">$1</h4>', #09
			'<div class="border">$1</div>', #10
	//		'—', #11
			'<pre class="codebox">$1</pre>', #12
			'<span class="highlight">$1</span>', #13
			//'<a href="$0" rel="nofollow">$0</a>', #14
			'$1$2<a href="$3" rel="nofollow" >$3</a>', #14
			//'<a href="http://$0" rel="nofollow">$0</a>', #15
			'$1$2<a href="http://$3" rel="nofollow" >$3</a>', #15
			'<strong>$1</strong>', #16
			'<em>$1</em>', #17
			'<h4 class="user">$1</h4>', #18
			'<span class="spoiler">$1</span>', #19
			'<div class="border">$1</div>', #20
			'<s>$1</s>', #21
			'<u>$1</u>', #22
			'<span class="highlight">$1</span>', #23
			'<a href="$1" rel="nofollow">$2</a>', #24
			'<sup>$1</sup>', #25
	//		'$1forum$2', #26
	//		'<a href="http://goatse.ru/">Facebook!</a>',
	//		'<a href="http://pastebin.com/uhWbw39T">$1</a>',
			'w',
			'W',
		);
	}

	public function formatAsHtml($text) {
		$text = htmlspecialchars($text, ENT_COMPAT | ENT_HTML401, "");
		$text = str_replace("\r", '', $text);
		
		$text = preg_replace($this->markup, $this->html, $text);
		
		return nl2br($text);
	}
	
	public function formatAsText($text, $nl2br, $encode=true) {
		$text = preg_replace($this->markup, '$1', $text);
		if($encode) $text = htmlspecialchars($text);
		if($nl2br) $text = nl2br($text);
		return $text;
	}
	
	public function sanitizeQuickQuote($text) {
		return $text;
	}
}
registerFormatter(0, new LegacyFormatter());