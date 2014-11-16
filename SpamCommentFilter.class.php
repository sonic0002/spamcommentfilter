<?php
/**
* Copyright (c) 2013 PixelsTech (http://www.pixelstech.net)
*
* All rights reserved.
*
* This script is free software.
*/

/**
* SpamCommentFilter to filter unwanted comments which contain advertisement or illegal contents
*
* Author : Pi Ke
*
* Usage:
*
*		 SpamCommentFilter::configure(array(["allowedLinkNum"=>4,"blacklist"=?array()]));  //This is optional
*                $isAllowed = SpamCommentFilter::isAllowed($comment);
*                $isAllowed = SpamCommentFilter::isAllowed($comment,SpamCommentFilter::IGNORE_LINK[|SpamCommentFilter::IGNORE_KEYWORD]);
*
*/
include_once('PorterStemmer.php'); //Change stemmer class accordingly
class SpamCommentFilter{
        //Black list array. You can change this by storing them in an external file or database
        private static $blacklist=array('ugg','asshole','hardcore','suck','sucks','coach','casino','sex','porn','credit','drug','drugs','pharmacy','preteen','pussy','fuck','fucking','chick','nude');
        //Allowed lik number, default value is 2
        private static $allowedLinkNum= 2;
        //Constant whether not to ignore any check
        const IGNORE_NONE    = 0x0000;
        //Constant whether to ignore link checks
        const IGNORE_LINK    = 0x0001;
        //Constant whether to ignore keyword checks
        const IGNORE_KEYWORD = 0x0010;
        //Constant whether to ignore all checks
        const IGNORE_ALL     = 0x0011;
        
        /*
         * @function    : configure()
         * @parameter   : $props -- Properties array contaiining link limit,blacklist etc
         * @return      : void
         * @throws      : Throw exception if $props is not an array
         * @description : Configure the spam comment filter
         */
        public static function configure($props){
        	if(is_array($props)){
        		if(isset($props["allowed_link_num"])){
        			SpamCommentFilter::setAllowedLinkNum($props["allowed_link_num"]);
        		}
        		if(isset($props["blacklist"])){
        			SpamCommentFilter::setBlackList($props["blacklist"]);
        		}
        	}else{
        		throw new Exception("Properties must be an array");
        	}
        }

        /*
         * @function    : setBlackList()
         * @parameter   : $blacklist -- Blacklist to be checked against
         * @return      : void
         * @description : Explicitly set the black list
         */
        public static function setBlackList($blacklist){
	        if(is_array($blacklist)){
			SpamCommentFilter::$blacklist=$blacklist;
		}
        }

        /*
         * @function    : getBlackList()
         * @parameter   : void
         * @return      : $blacklist -- The blacklist set for the filter
         * @description : Get the blacklist set for the spam comment filter
         */
        public static function getBlackList(){
        	return SpamCommentFilter::$blacklist;
        }

        /*
         * @function    : setAllowedLinkNum(()
         * @parameter   : $allowedLinkNum -- Allowed link numbers in the comment
         * @return      : void
         * @description : Explicitly set the allowed link number in the comment
         */
        public static function setAllowedLinkNum($allowedLinkNum){
        	SpamCommentFilter::$allowedLinkNum=intval($allowedLinkNum);
        }

        /*
         * @function    : getAllowedLinkNum(()
         * @parameter   : void
         * @return      : $allowedLinkNum -- Allowed link numbers in the comment
         * @description : Get the allowed link number in the comment
         */
        public static function getAllowedLinkNum(){
        	return SpamCommentFilter::$allowedLinkNum;
        }

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
         * @parameter   : $data       -- Data to be checked
         *                $ignoreFlag -- What to ignore check
         * @return      : returned status
         * @description : Check whether the contained data is allowed
         */
        public static function isAllowed($data,$ignoreFlag=SpamCommentFilter::IGNORE_NONE){
        	if(($ignoreFlag&SpamCommentFilter::IGNORE_LINK)!=SpamCommentFilter::IGNORE_LINK){
        		if(SpamCommentFilter::getLinkNum($data)>SpamCommentFilter::$allowedLinkNum){
            			return false;
            		}
        	}
        	if(($ignoreFlag&SpamCommentFilter::IGNORE_KEYWORD)!=SpamCommentFilter::IGNORE_KEYWORD){
        		if(SpamCommentFilter::getBannedKeywordNum($data)>0){
            			return false;
           	 	}
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
            return max(substr_count(strtolower($data),'href='),substr_count(strtolower($data),'http://')+substr_count(strtolower($data),'https://'));
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
