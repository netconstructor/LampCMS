<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms;

/**
 * Class for parsing the SubmittedProfile
 * which is an object representing data of
 * user profile that was sent to the server from
 * the edit profile interface. Usually this is
 * sent via Edit Profile form on the web
 * but can also be submitted by other means
 * in the future like via API
 *
 *
 * @author Dmitri Snytkine
 *
 */
class ProfileParser extends LampcmsObject
{
	/**
	 * User object of user whose profile being updated
	 *
	 * @var object of type User
	 */
	protected $oUser;


	/**
	 * @var object of type SubmittedProfile
	 */
	protected $oSubmitted;


	public function __construct(Registry $oRegistry){
		$this->oRegistry = $oRegistry;
	}

	/**
	 *
	 * Modify values in User object
	 * based on SubmittedProfile
	 *
	 * @param User $oUser
	 * @param SubmittedProfile $o
	 *
	 * @return bool true
	 */
	public function save(User $oUser, SubmittedProfile $o){
		$this->oUser = $oUser;
		$this->oSubmitted = $o;

		$oUser['fn'] = $this->getClean($o->getFirstName())->substr(0, 60)->valueOf();
		$oUser['mn'] = $this->getClean($o->getMiddleName())->substr(0, 60)->valueOf();
		$oUser['ln'] = $this->getClean($o->getLastName())->substr(0, 80)->valueOf();
		$oUser['country'] = $this->getClean($o->getCountry())->substr(0, 60)->valueOf();
		$oUser['state'] = $this->getClean($o->getState())->substr(0, 50)->valueOf();
		$oUser['city'] = $this->getClean($o->getCity())->substr(0, 80)->valueOf();
		$oUser['url'] = $this->getUrl($this->getClean($o->getUrl()));
		$oUser['zip'] = $this->getClean($o->getZip())->substr(0, 8)->valueOf();
		$oUser['dob'] = $this->getDob($o->getDob());
		$oUser['gender'] = $this->getGender($o->getGender());
		$oUser['description'] = \wordwrap($this->getClean($o->getDescription())->substr(0, 2000)->valueOf(), 50);
		
		$this->makeAvatar();
		
		$oUser->save();

		return true;

	}


	/**
	 * Validates Dob string and returns it only if
	 * it looks valid, otherwise returns null
	 * @param string $string
	 * @return mixed string|null null if input does not
	 * look like a valid date of birth
	 */
	protected function getDob($string){
		return (Validate::validateDob($string)) ? $string : null;

	}


	/**
	 * Returns value of "Gender" but only
	 * if it's valid, otherwise returns null
	 * Enter description here ...
	 * @param string $str
	 * @return string M or F or empty string
	 */
	protected function getGender($str){
		d('gender string: '.$str);

		return ('M' === $str ||  'F'  === $str) ? $str : null;
	}


	/**
	 * Get value of url
	 * append 'http://' if url does not appear
	 * to be starting with the http prefix
	 * @param Utf8String $str
	 * @return string
	 */
	protected function getUrl(Utf8String $str){
		$str = $str->substr(0, 250)->trim()->valueOf();
		if('http' !== \substr($str, 0, 4)){
			return 'http://'.$str;
		}

		return $str;
	}


	/**
	 * Get clean UTF8String object representing
	 * trimmed and clean of html tags
	 *
	 * @param string $string
	 * @return object of type UTF8String
	 */
	protected function getClean($string){
		if(empty($string)){
			Utf8String::factory('', 'ascii', true );
		}

		return Utf8String::factory($string)->trim()->stripTags();
	}


	/**
	 * This will create avatar of square size
	 * and add path to User['avatar'] IF
	 * avatar has been uploaded
	 *
	 * @return object $this
	 */
	protected function makeAvatar(){
		$pathUpload = $this->oSubmitted->getUploadedAvatar();

		AvatarParser::factory($this->oRegistry)->addAvatar($this->oUser, $pathUpload);//$this->createAvatar($pathUpload);

		return $this;
	}

}