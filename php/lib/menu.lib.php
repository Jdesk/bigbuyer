<?php
class Menu {

    private $menu, $request;

    public function __construct($menu) {
		global $REQUEST;
		$this->request = $REQUEST;
		
        $this->menu = $menu;
    }
	public function getNode($key,$parent=array()){
		if(!$parent){$parent = $this->menu;}		
		$levels = explode("/",$key);
		$cur = array_shift($levels);

		$item = $parent[$cur];			
		if($levels && $item && $item['submenu']){	
			return $this->getNode(implode("/",$levels),$item['submenu']);
		}
		else{
			return (object)$item;
		}		
	}
    public function getMenu() {
        $menu = array();
        foreach ($this->menu as $k => $v)
            if (!$v['hidden'])
                $menu[$k] = $v;
        return $menu;
    }

    public function getSubMenu($k) {
        $menu = array();
        $sub = $this->menu[$k]['submenu'];
        if (!empty($sub))
            foreach ($sub as $k => $v)
                if (!$v['hidden'])
                    $menu[$k] = $v;
					
        return $menu;
    }
	public function isCurrentNode($key){
		$pages = explode("/",$this->request->page);
		return in_array(self::strip($key),$pages);
	}
    public function getCurrentPage() {
        global $REQUEST;

        $pages = explode("/", $this->request->page);
		
        foreach ($this->menu as $k => $item) {
            $item['key'] = $k;
            if (self::strip($k) == end($pages))
                return (object) $item;
            if ($item['submenu']) {
                foreach ($item['submenu'] as $subk => $subitem) {
                    if (self::strip($subk) == end($pages))
                        return (object) $subitem;
                }
            }
        }
        return false;
    }

    public function getCrumbs() {
        global $REQUEST;
        $pages = explode("/", $REQUEST->request);

        //$crumbs = array($GLOBALS['system']['href_base'] . $GLOBALS['site']['defaultpage'] => $this->menu[$GLOBALS['site']['defaultpage']]['title']);
		$crumbs = array($GLOBALS['system']['href_base'] . $GLOBALS['site']['defaultpage'] => 'Home');

        foreach ($pages as $req) {
            foreach ($this->menu as $k => $item) {
                $item['key'] = $k;
                if ($k == $req) {
					 if ($item['submenu']){
						$k = $k . '/' .key($item['submenu']);
					 }
                    $crumbs[$GLOBALS['system']['href_base'] . $k] = $item['title'];
                    continue;
                }
								
                if ($item['submenu']) {
                    foreach ($item['submenu'] as $subk => $subitem) {
						$subk = self::strip($subk);
                        if ($subk == $req) {
                            $crumbs[$GLOBALS['system']['href_base'] . $k . '/' . $subk] = $subitem['title'];
                            continue;
                        }
                    }
                }
            }
        }	
		
        $temp = array_pop($crumbs);
        $crumbs['#'] = $temp;	
		
        //$cur = $this->getCurrentPage();
        //if($cur->key != $GLOBALS['site']['defaultpage'])
//			$crumbs['#'] = $cur->title;
        return $crumbs;
    }
	
	static function strip($key){
		return str_replace("/","",$key);
	}

}

