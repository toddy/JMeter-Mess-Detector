<?php
namespace Phm\Jmmd\Filter;
use Phm\Jmmd\Rule\Rule;

use Phm\Jmmd\JMeter\HttpSampleElement;

class UrlBlackListFileFilter
{
	private $urlBlacklistUrls = array();
	
	public function addUrlBlackListFileFilter()
	{
		$blacklist = fopen("Phm/Jmmd/examples/urlblacklist.txt","r");
		while(!feof($blacklist))
		{
			$urlline = fgets($blacklist,1024);
			$this->addHttpSampleElement($urlline);
		}
		fclose($blacklist);
	}
	
	public function addHttpSampleElement($urlline)
	{
		$this->url_list[] = $urlline;
	}
	
	public function isFiltered ($url, Rule $rule)
	{
		foreach ($this->url_list as $blacklisturl) {
			if (trim($blacklisturl) == $url) {
                return true;
            }
        }
        return false;
    }
}