<?php
    /**
    * Copyright (c) 2013 PixelsTech (http://www.pixelstech.net)
    *
    * All rights reserved.
    *
    * This script is free software.
    */

  /**
   * SpamCommentFilter to filter come unwanted comments which contain advertisement or illegal contents
   * 
   * Usage:
   *     
   *		$isAllowed = SpamCommentFilter::isAllowed($comment);
   *
   **/
include_once("PorterStemmer.php'); //Change stemmer class accordingly
class SpamCommentFilter{
	//Black list array. You can change this by storing them in an external file or database
	public static $blacklist=array('ugg','asshole','hardcore','suck','sucks','coach','casino','sex','porn','credit','drug','drugs','pharmacy','preteen','pussy','fuck','fucking','chick','nude');
	//The link number in a comment
	const LINK_LIMIT = 2;
	
	/*
	 * @function    : getInputTokens()
	 * @parameter   : $data--Data to be tokenized, should be a string
	 * @return      : returned tokenized array
	 * @description : Return the tokens in a string
	 */
	private static function getInputTokens($data){
		return array_map('strtolower',preg_split('/[\s,!\?\.:]+/',$data));
	}
	
	/*
	 * @function    : getStemmedTokens()
	 * @parameter   : $tokens
	 * @return      : returned tokenized array which is stemmed
	 * @description : Return the stemmed tokens
	 */
	private static function getStemmedTokens($tokens){
		return array_map("PorterStemmer::Stem",$tokens);
	}
	
	/*
	 * @function    : getUniqueTokens()
	 * @parameter   : $tokens -- Array to be checked
	 * @return      : returned tokenized array which contains unique elements
	 * @description : Return the unique tokens
	 */
	private static function getUniqueTokens($tokens){
		return array_unique($tokens);
	}
	
	/*
	 * @function    : isAllowed()
	 * @parameter   : $data--Data to be checked
	 * @return      : returned status
	 * @description : Check whether the contained data is allowed
	 */
	public static function isAllowed($data=''){
		if(SpamCommentFilter::getLinkNum($data)>=SpamCommentFilter::LINK_LIMIT||SpamCommentFilter::getBannedKeywordNum($data)>0){
			return false;
		}
		return true;
	}
	
	/*
	 * @function    : getLinkNum()
	 * @parameter   : $data--Data to be checked
	 * @return      : returned number of links contained in the data
	 * @description : Check number of links in the data
	 */
	public static function getLinkNum($data=''){
		return max(substr_count(strtolower($data),'href='),substr_count(strtolower($data),'http://'));
	}

	/*
	 * @function    : getBannedKeywordNum()
	 * @parameter   : $data--Data to be checked
	 * @return      : returned number of banned keywords
	 * @description : Check number of banned keywords
	 */
	public static function getBannedKeywordNum($data=""){
		SpamCommentFilter::$blacklist=array_map("strtoupper",SpamCommentFilter::$blacklist);
		$data=trim(preg_replace('/<[^>]*>/', ' ', $data));
		$inputTokens=SpamCommentFilter::getInputTokens($data);
		$stemmedTokens=SpamCommentFilter::getStemmedTokens($inputTokens);
		$uniqueTokens=SpamCommentFilter::getUniqueTokens($stemmedTokens);
		$uniqueTokensSet2=SpamCommentFilter::getUniqueTokens($inputTokens);
		return count(array_unique(
						array_merge(
							array_intersect(array_map("strtoupper",$uniqueTokens),SpamCommentFilter::$blacklist),array_intersect(array_map("strtoupper",$uniqueTokensSet2),SpamCommentFilter::$blacklist)
						)
					)
			   );
	}
}
?>
