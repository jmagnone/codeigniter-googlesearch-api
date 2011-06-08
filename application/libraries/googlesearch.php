<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * googlesearch
 * 
 * Google Search API for CodeIgniter
 * 
 * Query returns an object with Google Search results, meta data and some other useful
 * information like similar terms. This is useful for example for those looking to 
 * build applications where similar terms matter.
 * 
 * Resources:
 * http://www.assembla.com/wiki/show/SAMS/Google_standard_search_arguments
 * 
 * @package googlesearch
 * @author Julian Magnone (julianmagnone@gmail.com)
 * @copyright 2011
 * @version $Id$
 * @access public
 */
class googlesearch
{
    private $ci;
    
    private $_api_url = "http://ajax.googleapis.com/ajax/services/search/";
    private $searchers = array('web', 'local', 'video', 'blogs', 'news', 'books', 'images', 'patent');
    private $_default_searcher = 'web';
    
    public function __construct($params = null)
    {
        // do something with params
        $this->ci =& get_instance();

        // config values
        //$this->ci->config->item('');
    }
    
    /**
     * keywordsuggest::query()
     * 
     * Run a query in Google suggest and get suggestions for a given keyword, also 
     * return the number of queries for each suggeston.
     * 
     * @return
     */
    public function query($q)
    {
        $url = $this->_api_url.'&q='.urlencode($q);
        
        $res = $this->_query($q);
        
        return $res;
    }
    
    /**
     * googlesearch::_query()
     * 
     * hl value is optional argument supplies the host language of the application making the request.
     * If this argument is not present then the system will choose a value based on the value of the Accept-Language
     * http header. If this header is not present, a value of en is assumed. 
     * 
     * Returns an array composed by:
     * {
     *  results  returns the list of results from Google results pages
     *  meta     returns the cursor and number of searches from Google's results
     *  similar  a list of similar keywords after using ~ 
     * }
     * 
     * @return
     */
    function _query($searchterm, $start = 0, $hl = null)
    {
        $result_array = array();
        $result_similar = array(); // collect similar terms when used with ~
        
        // Prepare query and url
        $rsz = 'large';       
        $url = $this->_api_url.$this->_default_searcher.'?v=1.0&q='.urlencode($searchterm).'&start='.$start.'&rsz='.$rsz;
        if (!empty($hl)) $url .= '&hl='.urlencode($hl);
        
        // Use curl to call Google search
        $searchterm = urlencode($searchterm);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, site_url() );
        $body = curl_exec($ch);
        curl_close($ch);
        $string = json_decode($body);

        foreach ($string->responseData->results as $val)
        {
            $newval = (array)$val;
            
            $title = $newval['title'];
            $content = $newval['content'];            
            
            preg_match("'<b>(.*?)</b>'si", $title.$content, $match);
            if($match) $bold = strtolower( $match[1] );
            
            if (!empty($bold) AND !in_array($bold,$result_similar) ) $result_similar[] = $bold;
            
            $result_array[] = (array) $val;
        }
        
        $result_meta = (array) $string->responseData->cursor;

        $result = array(
                    'results' => $result_array,
                    'meta' => $result_meta,
                    'similar' => $result_similar,
                );        

        return $result;
    }
    
}

/* End of file googlesearch.php */