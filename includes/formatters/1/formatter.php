<?php
require_once "JBBCode/Parser.php";
require_once "JBBCode/CodeDefinition.php";
require_once "JBBCode/CodeDefinitionBuilder.php";
require_once "JBBCode/NodeVisitor.php";
require_once "JBBCode/visitors/HTMLSafeVisitor.php";

class LiTag extends JBBCode\CodeDefinition {
	public function __construct() {
		parent::__construct();
		$this->setTagName("li");
		$this->setReplacementText("<li>{param}</li>");
	}
	
	public function asHtml(JBBCode\ElementNode $el) {
		if(
			!($el->getParent() instanceof JBBCode\ElementNode)
			|| !in_array($el->getParent()->getTagName(), array("ul", "ol"))
		)
			return $el->getAsBBCode();

		return parent::asHtml($el);
	}
}

class CodeTag extends JBBCode\CodeDefinition {
	public function __construct() {
		parent::__construct();
		$this->setTagName("code");
	}
	
	public function asHtml(JBBCode\ElementNode $el) {
		$content = "";
		foreach ($el->getChildren() as $child)
			$content .= $child->getAsBBCode();
		
		$content = preg_replace('/^\n|\n$/', '', $content);
		
		return "<pre class='codebox2'>" . $content . "</pre>";
	}
}

class HTMLSafeVisitor extends JBBCode\visitors\HTMLSafeVisitor {
	public function visitElementNode(\JBBCode\ElementNode $elementNode) {
		$attrs = $elementNode->getAttribute();
		if (is_array($attrs)) {
			foreach ($attrs as &$el)
				$el = $this->htmlSafe($el, ENT_QUOTES | ENT_COMPAT | ENT_HTML401);

			$elementNode->setAttribute($attrs);
		}

		foreach ($elementNode->getChildren() as $child) {
			$child->accept($this);
		}
	}

	protected function htmlSafe($str, $options = null) {
		if (is_null($options)) {
			$options = ENT_COMPAT | ENT_HTML401;
		}

		return htmlspecialchars($str, $options, 'UTF-8');
	}
}

class LegacyMarkupVisitor implements JBBCode\NodeVisitor {
	protected $html;
	protected $markup;
	
	public function __construct() {
		$this->markup = array ( 
			// 06 Linkify text in the form of [http://example.org text].
			'@\[(https?|ftps?)://([A-Z0-9/&#+%~=_+|?.,!:$;\-\@]+) (.+?)\]@i',
			// 07 Internal links.
			'/\[il\](.+?)\[\/il\]/',
			// 08 Quotes.
			'/^&gt;(.*)$/m',
			// 14 Make some cool links.
			'/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is',
			// 15 More url stuff
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
			'/\%\%(.+?)\%\%/'
		);
			
		$this->html = array (
			'<a href="$1://$2" title="$1://$2" rel="nofollow">$3</a>', #06
			'<em class="unimportant">(<a href="$1" rel="nofollow">topic $1</a>)</em>', #07
			'<span class="quote"><strong>></strong> $1</span>', #08
			'$1$2<a href="$3" rel="nofollow" >$3</a>', #14
			'$1$2<a href="http://$3" rel="nofollow" >$3</a>', #15
			'<strong>$1</strong>', #16
			'<em>$1</em>', #17
			'<h4 class="user">$1</h4>', #18
			'<span class="spoiler">$1</span>', #19
			'<div class="border">$1</div>', #20
			'<s>$1</s>', #21
			'<u>$1</u>', #22
			'<span class="highlight">$1</span>', #23
		);
	}
	
	public function visitDocumentElement(JBBCode\DocumentElement $documentElement) {
		foreach ($documentElement->getChildren() as $child) {
			$child->accept($this);
		}
	}
	
	public function visitElementNode(JBBCode\ElementNode $elementNode) {
		if($elementNode->getTagName() == "code") return;
		
		foreach ($elementNode->getChildren() as $child) {
			$child->accept($this);
		}
	}
	
	public function visitTextNode(JBBCode\TextNode $textNode) {
		$textNode->setValue(preg_replace($this->markup, $this->html, $textNode->getValue()));
	}
}

class NewlineVisitor implements JBBCode\NodeVisitor {
	public function visitDocumentElement(JBBCode\DocumentElement $documentElement) {
		foreach ($documentElement->getChildren() as $child) {
			$child->accept($this);
		}
	}
	
	public function visitElementNode(JBBCode\ElementNode $elementNode) {
		if(in_array($elementNode->getTagName(), array("code", "ul", "ol"))) return;
		
		foreach ($elementNode->getChildren() as $child) {
			$child->accept($this);
		}
	}
	
	public function visitTextNode(JBBCode\TextNode $textNode) {
		$textNode->setValue(nl2br($textNode->getValue()));
	}
}

class CustomizedBBCodeFormatter extends JBBCode\Parser implements MinichanFormatter {
	protected $visitors = array();

	public function __construct() {
		parent::__construct();
		$urlValidator = new JBBCode\validators\UrlValidator();
		$colorValidator = new JBBCode\validators\CssColorValidator();
		
		$this->addCodeDefinition(new CodeTag());
		$this->addCodeDefinition(new LiTag());
		
		$this->addBBCode("ul", '<ul>{param}</ul>');
		$this->addBBCode("ol", '<ol>{param}</ol>');
		
		$this->addBBCode('b', '<strong>{param}</strong>');
		$this->addBBCode('i', '<em>{param}</em>');
		$this->addBBCode('u', '<u>{param}</u>');
		$this->addBBCode('s', '<s>{param}</s>');
		$this->addBBCode('h', '<h4 class="user">{param}</h4>');
		$this->addBBCode('border', '<div class="border">{param}</div>');
		$this->addBBCode('hl', '<span class="highlight">{param}</span>');
		$this->addBBCode('sup', '<sup>{param}</sup>');
		$this->addBBCode('sub', '<sub>{param}</sub>');
		
		$this->addBBCode('sp', '<span class="spoiler">{param}</span>');
		$this->addBBCode('spoiler', '<span class="spoiler">{param}</span>');
		
		$this->addCodeDefinition((new JBBCode\CodeDefinitionBuilder('url', '<a href="{param}">{param}</a>'))->setParseContent(false)->setBodyValidator($urlValidator)->build());
		$this->addCodeDefinition((new JBBCode\CodeDefinitionBuilder('url', '<a href="{option}">{param}</a>'))->setUseOption(true)->setParseContent(false)->setOptionValidator($urlValidator)->build());
		
		$this->addCodeDefinition((new JBBCode\CodeDefinitionBuilder('color', '<span style="color: {option}">{param}</span>'))->setUseOption(true)->setOptionValidator($colorValidator)->build());
		$this->addCodeDefinition((new JBBCode\CodeDefinitionBuilder('colour', '<span style="color: {option}">{param}</span>'))->setUseOption(true)->setOptionValidator($colorValidator)->build());
		
		$this->visitors[] = new HTMLSafeVisitor();
		$this->visitors[] = new LegacyMarkupVisitor();
		$this->visitors[] = new NewlineVisitor();
	}
	
	public function parse($text, $nl2br=true, $encode=true) {
		$text = str_replace("\r", '', $text);
		parent::parse($text);
		foreach($this->visitors as $visitor) {
			if(!$encode && $visitor instanceof HTMLSafeVisitor) continue;
			if(!$nl2br && $visitor instanceof NewlineVisitor) continue;
			
			$this->accept($visitor);
		}
		return $this;
	}
	
	public function formatAsHtml($text) {
		$this->parse($text);
		return $this->getAsHtml();
	}
	
	public function formatAsText($text, $nl2br, $encode=true) {
		$this->parse($text, $nl2br, $encode);
		return $this->getAsText();
	}
}
registerFormatter(1, new CustomizedBBCodeFormatter());