<?php
namespace In2code\Fetchurl\Domain\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Alex Kellner <alexander.kellner@in2code.de>, in2code.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class FetchService
 * @package In2code\Fetchurl\Domain\Service
 */
class FetchService
{

    /**
     * URL to parse
     *
     * @var string
     */
    protected $url = '';
	
	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;  

    /**
     * @return string
     */
    public function fetchUrl($url)
    {
        $this->url = $url;
        $html = $this->getContentFromUrl();
        $html = $this->getBodyContent($html);
		// Signal to modify the fetched $html content
		$this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__ , [&$html, $this]);
//        $html = $this->replaceDomain($html);
        return $html;
    }

    /**
     * @return string
     */
    protected function getContentFromUrl()
    {
        return GeneralUtility::getUrl($this->url);
    }

    /**
     * Get content between <body> and </body>
     *
     * @param string $html
     * @return string
     */
    protected function getBodyContent($html)
    {
        if (preg_match('/<body .*?>(.*)<\/body/si', $html, $matches)) {
            $html = $matches[1];
        }
        
        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    protected function replaceDomain($html)
    {
        $patterns = [
            '/src="(\w[^:]+?)"/i',
            '/src="\/([^:]+?)"/i',
            '/href="(\w[^:]+?)"/i',
            '/href="\/([^:]+?)"/i'
        ];
        $replacements = [
            'src="' . $this->getCurrentDomain() . '/$1"',
            'src="' . $this->getParsingDomain() . '/$1"',
            'href="' . $this->getCurrentDomain() . '/$1"',
            'href="' . $this->getParsingDomain() . '/$1"'
        ];
        $html = preg_replace($patterns, $replacements, $html);
        return $html;
    }

    /**
     * @return string
     */
    protected function getParsingDomain()
    {
        $urlParts = parse_url($this->url);
        return $urlParts['scheme'] . '://' . $urlParts['host'];
    }

    /**
     * @return string
     */
    protected function getCurrentDomain()
    {
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
    }
}
