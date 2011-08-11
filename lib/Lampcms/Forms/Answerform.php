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
 *    the website's Questions/Answers functionality is powered by lampcms.com
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


namespace Lampcms\Forms;


use \Lampcms\LoginForm;
use \Lampcms\String\HTMLString;
/**
 * Class responsible for processing the
 * Answer form as well as rendering the ask form
 *
 * @author Dmitri Snytkine
 *
 */
class Answerform extends Form
{

	/**
	 * Name of form template file
	 * The name of actual template should be
	 * set in sub-class
	 *
	 * @var string
	 */
	protected $template = 'tplFormanswer';

	/**
	 * Generates the html of the answer form
	 * OR an html block explaining why form is not
	 * available (like when question is marked as closed)
	 * and also setting the 'mustLogin' related elements
	 * if user not logged in
	 *
	 * @param Question $oQuestion
	 *
	 * @return string html of form or message block
	 */
	public function getAnswerForm(\Lampcms\Question $oQuestion){

		d('cp');

		
		/**
		 * A custom observer may cancel the onBeforeAnswerform event
		 * in order to NOT display the form
		 * For example, a filter (implemented as an observer)
		 * may decide not to show an answer form to users
		 * from rough states based on GeoLocation by IP
		 * For example - don't show answer form in China
		 * OR NOT show answer form based on Viewer object if viewer
		 * has been flagged as spammer
		 */
		$oNotification = $this->oRegistry->Dispatcher->post($oQuestion, 'onBeforeAnswerForm');
		d('cp');
		if($oNotification->isNotificationCancelled()){
			d('onBeforeAnswerform cancelled by observer');

			return '';
		}
		d('cp');
		/**
		 * If questions is closed then
		 * instead of answer form return
		 * the div with "closed" message
		 */
		if(false !== $aClosed = $oQuestion->isClosed()){

			return \tplClosedby::parse($aClosed);
		}
		d('cp');


		$formTitle = (0 === $oQuestion['i_ans']) ? $this->Tr['Be the first to answer this question'] : $this->Tr['Your answer'] ;

		$this->setVar('title', $formTitle);
		$this->setVar('qid', $oQuestion['_id']);
		$this->setVar('submit', $this->Tr['Submit answer']);
		$this->setVar('preview', $this->Tr['Preview']);

		if($this->oRegistry->Viewer->isGuest()){
			d('cp');
			$this->qbody = $this->_('Please login to post your answer');
			$this->com_hand = ' com_hand';
			$this->readonly = 'readonly="readonly"';
			$this->disabled = ' disabled="disabled"';

			$oQuickReg = new \Lampcms\RegBlockQuickReg($this->oRegistry);
			d('cp');
			$socialButtons = LoginForm::makeSocialButtons($this->oRegistry);
			/**
			 * @todo Translate string
			 */
			if(!empty($socialButtons)){
				$this->connectBlock = '<div class="com_connect"><h3>Join with account you already have</h3>'.$socialButtons.'</div>';
			}
		}
		d('cp');
		$form = $this->getForm();

		return $form;

	}


	/**
	 * Enforce config values MIN_ANSWER_CHARS
	 * and MIN_ANSWER_WORDS
	 * 
	 * @todo Translate strings of error messages
	 *
	 * (non-PHPdoc)
	 * @see Lampcms\Forms.Form::doValidate()
	 *
	 * @return object $this
	 */
	protected function doValidate(){

		$minChars = $this->oRegistry->Ini->MIN_ANSWER_CHARS;
		$minWords = $this->oRegistry->Ini->MIN_ANSWER_WORDS;
		$body = $this->oRegistry->Request->getUTF8('qbody');
		$oHtmlString = HTMLString::factory($body);
		$wordCount = $oHtmlString->getWordsCount();
		$len = $oHtmlString->length();

		if($len < $minChars){
			$this->setError('qbody', 'Answer must contain at least '.$minChars.' letters');
		}

		if($wordCount < $minWords){
			$this->setError('qbody', 'Answer must contain at least '.$minWords.' words');
		}

		return $this;
	}

}
