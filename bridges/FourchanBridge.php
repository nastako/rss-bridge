<?php
class FourchanBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "4chan";
	const URI = "https://www.4chan.org/";
	const DESCRIPTION = "Returns posts from the specified thread";

    const PARAMETERS = array( array(
        't'=>array(
            'name'=>'Thread URL',
            'pattern'=>'(https:\/\/)?boards\.4chan\.org\/.*thread\/.*',
            'required'=>true
        )
    ));

  public function collectData(){

      $thread = parse_url($this->getInput('t'))
          or $this->returnClientError('This URL seems malformed, please check it.');
	if($thread['host'] !== 'boards.4chan.org')
		$this->returnClientError('4chan thread URL only.');

	if(strpos($thread['path'], 'thread/') === FALSE)
		$this->returnClientError('You must specify the thread URL.');

	$url = 'https://boards.4chan.org'.$thread['path'];
    $html = $this->getSimpleHTMLDOM($url)
        or $this->returnServerError("Could not request 4chan, thread not found");

	foreach($html->find('div.postContainer') as $element) {
		$item = array();
		$item['id'] = $element->find('.post', 0)->getAttribute('id');
		$item['uri'] = $url.'#'.$item['id'];
		$item['timestamp'] = $element->find('span.dateTime', 0)->getAttribute('data-utc');
		$item['author'] = $element->find('span.name', 0)->plaintext;

        $file=$element->find('.file', 0);
		if(!empty($file) ) {
			$item['image'] = $element->find('.file a', 0)->href;
			$item['imageThumb'] = $element->find('.file img', 0)->src;
			if(!isset($item['imageThumb']) and strpos($item['image'], '.swf') !== FALSE)
				$item['imageThumb'] = 'http://i.imgur.com/eO0cxf9.jpg';
		}
		if(!empty($element->find('span.subject', 0)->innertext )) {
			$item['subject'] = $element->find('span.subject', 0)->innertext;
		}
		$item['title'] = (isset($item['subject']) ? $item['subject'].' - ' : '' ) . 'reply '.$item['id'].' | '.$item['author'];


		$item['content'] = (isset($item['image']) ? '<a href="'.$item['image'].'"><img alt="'.$item['id'].'" src="'.$item['imageThumb'].'" /></a><br>' : '') . '<span id="'.$item['id'].'">'.$element->find('.postMessage', 0)->innertext.'</span>';
		$this->items[] = $item;
	}
	$this->items = array_reverse($this->items);
  }

    public function getCacheDuration(){
        return 300; // 5min
    }
}
