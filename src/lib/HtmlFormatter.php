<?php
Utils::import('strings');

define('HTML_HEADINGS', 1);
class HtmlFormatter {
	var $text;
	var $formatted_text;

	/**
	 * A list of elements to skip formatting. The element is defined by the constants in this class, and must 
	 * be set as the key in order to disable formatting.
	 */
	var $disabled = array();

	function HtmlFormatter ($text) {
		$this->text = $text;
		$this->formatted_text = '';
	}

	/**
	 * Disabled formatting for the elements represented by the supplied element constant.
	 *
	 * @param int $elements		Constant representing the element(s) to not format.
	 */
	function disable_formatting ($elements) {
		$this->disabled[$elements] = true;
	}

	function is_formatting_enabled ($elements) {
		return !isset($this->disabled[$elements]);
	}

	/**
	 * Oversetter en tekststreng til gyldig XHTML med støtte for et utsnitt av Textile-formatering.
	 * Tekststrengen kan formateres som en selvstendig XHTML-blokk, hvorav alle tekstblokker vil legges i
	 * passende XHTML-elementer, eller man kan be om at teksten skal formateres som en enkeltstående
	 * tekstblokk som skal dyttes inn i et XHTML blokkelement senere.
	 *
	 * Textile-formatering som støttes er overskrifter, lister, avsnitt, lenker, bilder og utheving.
	 * Unntak er støtte for ekstra CSS og attributter til disse.
	 * Textiles hjemmeside: http://www.textism.com/tools/textile/
	 * Guide til Textile: http://hobix.com/textile/
	 *
	 * @param string $string	Teksten som skal oversettes.
	 * @param boolean $inline	<code>TRUE</code> om teksten skal anses som én tekstblokk som senere skal
	 *							plasseres i et XHTML blokkelement. <code>FALSE</code> om teksten skal
	 *							behandles som flere tekstblokker som skal ferdigbehandles her.
	 * @return string XHTML-formatert versjon av teksten.
	 */
	function toHtml ($inline = false) {
		$this->_prepare_format();

		// Del opp i separate blokker. Typiske blokker er overskrifter, avsnitt og lister.
		$blocks = explode("\n\n", $this->text);

		if (count($blocks) == 1 && $inline) {
			// Kun én tekstblokk som skal formateres som en ren tekststreng, ikke som et avsnitt e.l.
			$block = new TextBlock($this, $this->text);
			return $block->format_text();
		}
	
		// Formater hver blokk for seg
		foreach ($blocks as $b) {
			$block = new TextBlock($this, $b);
			$this->formatted_text .= $block->format();
		}

		return $this->formatted_text;
	}

	function toText ($inline = false) {
		// Splitt opp i blokker igjen
		$html = preg_replace('#(</(?:p|ul|ol|h\d)>)(<(?:p|ul|ol|h\d)[^>]*>)#i', "$1\n\n$2", $this->text);
		$blocks = explode("\n\n", $html);

		if (count($blocks) == 1 && $inline) {
			$block = new HtmlBlock($blocks[0]);
			return $block->unformat_text();
		}

		foreach ($blocks as $b) {
			$block = new HtmlBlock($b);
			$unformatted_blocks[] = $block->unformat();
		}
		
		$this->formatted_text = implode("\n\n", $unformatted_blocks);
		return xml_decode($this->formatted_text);
	}	

	function _prepare_format () {
		// Konvertér tegn som har spesiell mening i (X)HTML (&, < og >).
		$string = xml_escape($this->text, array('"', "'"));

		// Standardiser linjeskift til LF (\n) og maks to linjeskift etter hverandre
		$string = convert_linebreaks($string);
		$this->text = preg_replace('@\n{3,}@', "\n\n", $string);
	}
}

class TextBlock {
	var $text;
	var $formatter;

	function TextBlock (&$formatter, $text) {
		$this->formatter =& $formatter;
		$this->text = $text;
	}

	/**
	 * Finner ut hva slags type blokk det er (overskrift, avsnitt, liste) og sørger for å få den formatert
	 * etter type.
	 *
	 * @param string $block	Blokken med tekst som skal formateres.
	 * @return string Med XHTML-formatert tekst.
	 */
	function format () {
		if ($this->formatter->is_formatting_enabled(HTML_HEADINGS)
				&& preg_match('/^h[1-6]\. .+$/', $this->text) != 0) {
			// Tekstblokken er en overskrift
			return $this->format_heading();
		}
		else if (preg_match('/\A(?:(-|#)+ .+\n)*(?:\1+ .+(?:\n|\Z))/m', $this->text, $matches) != 0) {
			// Tekstblokken er en liste
			return $this->format_list($this->text, (substr($matches[1], 0, 1) == '#'));
		}
		
		// Tekstblokken er et vanlig avsnitt
		return "<p>".$this->format_text().'</p>';
	}

	/**
	 * Formaterer tekst som en overskrift.
	 *
	 * @param string $string	Teksten som skal være en overskrift.
	 * @return XHTML-formatert overskrift.
	 */
	function format_heading () {
		return preg_replace('/^h([1-6])\. (.+)$/', '<h$1>$2</h$1>', $this->text);
	}
	
	/**
	 * Rekursiv funksjon som genererer en XHTML-liste, enten ordnet eller uordnet.
	 *
	 * TODO: Denne er hacky implementert nå som TextBlock er klasse. Bør i stedet opprette innvendig tekst
	 * som nye TextBlock-er og formatere de (slippe å sende strenger rundt).
	 *
	 * @param string $string	Teksten som skal brytes opp og lages til liste.
	 * @param boolean $ordered	Om listen er ordnet (nummerert) eller uordnet.
	 * @return XHTML-formatert liste.
	 */
	function format_list ($string, $ordered = false) {
		$items = explode("\n", $string);
		$indent = strpos($items[0], ' ');
		$html = '';
		
		for ($i = 0; $i < count($items); $i++) {
			$html .= preg_replace('/^(#|-)+ /', '<li>', $this->format_text($items[$i]));

			if (isset($items[$i+1])) {
				$next = $i + 1;
				$next_indent = strpos($items[$next], ' ');
				
				if ($next_indent > $indent) { // We hit a sub-list
					$next_ordered = (substr($items[$next], ($next_indent - 1), 1) == '#');
					
					$sub_items = $items[$next];
					for ($i = $next + 1; $i <= count($items); $i++) {
						if (!isset($items[$i]) || strpos($items[$i], ' ') < $next_indent) {
							$html .= $this->format_list($sub_items, $next_ordered);
							--$i;
							break;
						}
						
						$sub_items .= "\n".$items[$i];
					}
				}
			}
			
			$html .= '</li>';
		}
		
		if ($ordered) {
			return '<ol>'.$html.'</ol>';
		}
		
		return '<ul>'.$html.'</ul>';
	}

	/**
	 * Tar seg av grunnleggende oversetting av tekstlig formatering til (X)HTML.
	 * 
	 * Har støtte for:
	 * _tekst_ blir til <em>tekst</em>
	 * *tekst* blir til <strong>tekst</strong>
	 * "Z-it Productions":http://z-it.no/ blir til <a href="http://z-it.no/">Z-it Productions</a>
	 * http://z-it.no blir til <a href="http://z-it.no">http://z-it.no</a>
	 * !http://z-it.no/logo.gif! blir til <img src="http://z-it.no/logo.gif" />
	 * !http://z-it.no/logo.gif(Z-it)! blir til <img src="http://z-it.no/logo.gif" alt="Z-it" title="Z-it" />
	 * 
	 * @param string $string	Tekst som skal oversettes
	 * @return string (X)HTML-ifisert tekst.
	 */
	function format_text ($string = null) {
		if ($string === null) {
			$string = $this->text;
		}

		// Korrigér formateringstegn for bilder
		$text = str_replace(
			array('!&gt;', '!&lt;'),
			array('!>', '!<'),
			$string
		);

		$formatting_patterns = array(
			'@
				(?<=^|[^a-z<>!])								# En link må være klart adskilt fra foregående
																# tekst, hindrer kollisjon med bilder
																
				(?:"([^"]+)":|(![^!]+!):)?						# Definisjon av linktekst
				(
					(?:&lt;)?									# Støtter URL pakket inn i < og >
					(?:
						(?:										# URL som starter med "www.", enten
							[a-z][a-z0-9+\-\.]*://	|			# med protokoll eller
							(?<!://)							# uten = ikke hopp over protokoll
						)www\.						|
						(?:[a-z][a-z0-9+\-\.]*://)				# URL med kun protokoll-spesifikasjon
					)
					(?:											# Resten av link består av...
						[/a-z0-9~\-_\.]+	|					# ...vanlige segmenter skilt med /
						%[a-f0-9]{2}	|						# ...hex-enkodede tegn (%20 f.eks.)
						[!=$&\@\'()*+?#,;:]+					# ...eller utvalgte særtegn
					)+
					(?:&gt;)?
				)
			 @xei',
			'@
				([a-z][a-z0-9_\-\.]+\@[a-z][a-z0-9_\-\.]+\.[a-z]{2,7})
			@xei',
			/*'@!([<>])?([^ !]+?)(\(.+\))?!@ei', // !bilde-url(tittel)! til <img src="bilde-url" alt="tittel" />*/
			'/(^|\s|\W|\b)_(.+?)_(\s|\W|\b|$)/s', // _tekst_ til <em>tekst</em>
			'/(^|\s|\W|\b)\*(.+?)\*(\s|\W|\b|$)/s' // *tekst* til <strong>tekst</strong>
		);
		
		$xhtml_replacements = array(
			'TextBlock::create_link("$3", (strlen("$1") == 0 ? "$2" : "$1"))',
			'TextBlock::create_link("mailto:$1", "$1")',
			/*'TextBlock::create_img("$2", "$1", "$3")',*/
			'$1<em>$2</em>$3',
			'$1<strong>$2</strong>$3'
		);

		$html = preg_replace($formatting_patterns, $xhtml_replacements, $text);
		return str_replace(array("\n", '%5F', '%2A'), array('<br />', '_', '*'), $html);
	}

	/**
	 * Lager en HTML-lenke ut i fra en URL, samt eventuell lenke-tekst og lenke-tittel.
	 * 
	 * Om lenke-tekst ikke er spesifisert blir URL brukt som dette.
	 * 
	 * @param string $url	Lenkens URL.
	 * @param string $text	Teksten som skal representere lenken, hvis annet enn URL i seg selv.
	 * @param string $title	Eventuell tittel på lenken.
	 * @return string HTML-lenke som peker til oppgitt adresse.
	 */
	function create_link ($url, $text = null, $title = null) {
		// Sørg for at <> rundt URI blir fjernet hvis linktekst er spesifisert, spesifikt ved:
		// "Min tittel":<http://blabla.com>
		$pre = $post = '';
		if ((substr($url, 0, 4) == '&lt;') && (substr($url, -4) == '&gt;')) {
			$url = substr($url, 4, -4);
			
			if (empty($text)) {
				$pre = '&lt;';
				$post = '&gt;';
			}
		}
		
		// Sørg for at ikke avsluttende punktum, utropstegn eller spørsmåltegn blir tatt med
		// i klikkbar link.
		if (preg_match('/(?:[\.!?_*()]+|&gt;)$/', $url, $match) == 1) {
			$post = $match[0] . $post;
			$url = substr($url, 0, -strlen($match[0]));
		}
		
		// Sørg for at linken har klikkbar tekst eller bilde, URL blir brukt om ikke annet er oppgitt.
		if ($text === null || $text == '') $text = $url;
		
		// Sørg for at URL starter med støttet protokoll, standard er HTTP.
		if (!preg_match('@^([a-z][a-z0-9+\-\.]*://|mailto:)@', $url)) $url = 'http://'.$url;
		
		return sprintf('%s<a href="%s"%s>%s</a>%s', $pre, $this->encode_uri($url),
				($title !== null ? ' title='.$title : ''), $text, $post);
	}

	/**
	 * Generer XHTML-kode for et bilde ut i fra URI til bildet samt evt. justering og alternativ tekst.
	 *
	 * @param string $uri	Adressen til bildet.
	 * @param string $align	'<' for å justere bildet til venstre, '>' for å justere til høyre. Standard
	 * 						er ingen justering.
	 * @param string $title	Eventuell alternativ tekst om bildet ikke kan vises.
	 */
	function create_img ($uri, $align = '', $title = '') {
		if (strpos($uri, 'http') !== 0) {
			// URL starter ikke med http:// eller https://, bruk merkelapp for malens URL
			$uri = '%url/'.$uri;
		}
		
		// Fjern parentes rundt tittel
		$title = substr($title, 1, -1);
		
		// Definér standard-attributter
		$attr = array(
			'title' => $title,
			'alt' => $title,
			'border' => 0
		);
		
		// Legg til eventuelle attributter for høyre-/venstrejustering
		if ($align == '<') {
			$attr['style'] = 'float:left; margin-right:1em';
			$attr['align'] = 'left';
		}
		else if ($align == '>') {
			$attr['style'] = 'float:right; margin-left:1em';
			$attr['align'] = 'right';
		}

		return sprintf('<img src="%s" %s />', $uri, $this->build_html_attributes($attr));
	}

	/**
	 * Enkoder Textile formateringstegn i en URI.
	 *
	 * @param string $uri	URI som skal enkodes.
	 * @return string Trygt enkodet URI.
	 */
	function encode_uri ($uri) {
		$chars = array(
			'_' => '%5F',
			'*' => '%2A'
		);
		
		return strtr($uri, $chars);
	}

	/**
	 * Omgjør en liste med attributter om til en tekststreng som kan dyttes inn i et HTML-element.
	 * Nøkkelverdiene for hvert element i listen brukes som attributtnavn.
	 * Eksempel:
	 * 		array('name' => 'demo', 'width' => 30)
	 * 		=> name="demo" width="30"
	 * @param array $attributes	Listen med attributter som det skal bygges tekststreng ut av.
	 */
	function build_html_attributes ($attributes) {
		$attr_list = array();
		
		foreach ($attributes as $name => $value) {
			$attr_list[] = sprintf('%s="%s"', $name, $value);
		}
		
		return implode(' ', $attr_list);
	}
}

class HtmlBlock {
	var $html;

	function HtmlBlock ($html) {
		$this->html = $html;
	}

	function unformat ($inline = false) {
		if ($inline) {
			return $this->unformat_html();
		}
		else if (preg_match('#^<p(?: class="([^"]+)")?>(.+)</p>$#', $this->html, $matches) != 0) {
			return $this->unformat_html($matches[2], $matches[1]);
		}
		else if (preg_match('#^<(ul|ol)>#i', $this->html, $matches) != 0) {
			return $this->unformat_list($this->html, ($matches[1] == 'ol'));
		}
		else if (preg_match('#^<h\d>#i', $this->html) != 0) {
			return $this->unformat_heading();
		}
	}

	function unformat_list ($html, $ordered = false, $level = 1) {		
		$html = preg_replace('#^<(ul|ol)>(.+)</\1>$#', '\\2', $html);

		$text = '';
		$buffer = '';
		$li_level = 0;
		for ($i = 0; $i < strlen($html); $i++) {
			$c = substr($html, $i, 1);
			
			if ($c == '<') {
				$end_tag = strpos($html, '>', $i);
				$tag = substr($html, $i+1, $end_tag - ($i+1));
				
				if ($tag == 'li') {
					if ($li_level++ > 0) {
						$buffer .= "<$tag>";
					}
				}
				else if ($tag == '/li') {
					if (--$li_level == 0) {
						$text .= $this->unformat_list_item($buffer, $ordered, $level);
						$buffer = '';
					}
					else {
						$buffer .= "<$tag>";
					}
				}
				else {
					$buffer .= "<$tag>";
				}
				
				$i = $end_tag;
			}
			else if ($li_level > 0) {
				$buffer .= $c;
			}
		}
		
		return chop($text);
	}
	
	function unformat_list_item ($item, $ordered, $level = 1) {
		if ($ordered) {
			$prefix = str_repeat('#', $level);
		}
		else {
			$prefix = str_repeat('-', $level);
		}
		
		if (strpos($item, '<ul>') !== false || strpos($item, '<ol>') !== false) {
			preg_match('#<(ul|ol)>.+</\1>#', $item, $match);
			
			$item_text = substr($item, 0, strpos($item, $match[0]));
			
			return $prefix.' '.$this->unformat_html($item_text)."\n".
				$this->unformat_list($match[0], ($match[1] == 'ol'), ++$level)."\n";
		}
		
		return $prefix.' '.$this->unformat_html($item)."\n";
	}

	function unformat_heading () {
		return preg_replace('#^<(h(\d))>(.+)</\1>$#i', 'h$2. $3', $this->html);
	}

	function unformat_html ($html = null, $alignment = null) {
		if ($html === null) {
			$html = $this->html;
		}

		$text = str_replace('<br />', "\n", $html);

		$formatting_patterns = array(
			// <a href="http://url.no">tekst</a> => "tekst":http://url.no:
			'@<a href="([^"]+)">(.+?)</a>@ei',
			
			// _tekst_ and [i]tekst[/i] => <em>tekst</em>:
			'@(^|\s|\W|\b)
				<em>
				(.+?)
				</em>
			 (\s|\W|\b|$)@six',
			
			 // *tekst* and [b]tekst[/b] => <strong>tekst</strong>:
			'@(^|\s|\W|\b)
				<strong>
				(.+?)
				</strong>
			 (\s|\W|\b|$)@six'
		);
		
		$text_replacements = array(
			'HtmlBlock::unpack_link("$1", "$2")',
			'$1_$2_$3',
			'$1*$2*$3'
		);
		
		$text = preg_replace($formatting_patterns, $text_replacements, $text);

		// Bare "husker" denne litt inntil videre -- XSL skal gi oss automagisk escaping
		//html_decode(preg_replace($formatting_patterns, $text_replacements, $text));

		/*if ($alignment == 'alignCenter') {
			return '[C]'.$text.'[/C]';
		}
		else if ($alignment == 'alignRight') {
			return '[R]'.$text.'[/R]';
		}*/
		
		return $text;
	}

	function unpack_link ($url, $text) {
		if (substr($url, 0, 7) == 'mailto:') {
			$url = substr($url, 7);
		}

		return ($url == $text ? $url : sprintf('"%s":%s', $text, $url));;
	}
}
?>
