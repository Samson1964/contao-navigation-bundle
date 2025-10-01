<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   fh-counter
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

namespace Schachbulle\ContaoNavigationBundle\Classes;

class Navigation extends \Frontend
{

	public function get($strTag)
	{
		global $objPage;
		
		$arrSplit = explode('::', $strTag);
		$content = '';

		// Inserttag {{artikelnavigation::id}}
		// Liefert zur aktuellen bzw. gewünschten (id) Seite eine Artikelnavigation
		if($arrSplit[0] == 'artikelnavigation' || $arrSplit[0] == 'artikelnavigation_alter')
		{
			if(!isset($GLOBALS['Navigationszaehler'])) $GLOBALS['Navigationszaehler'] = 1;
			else $GLOBALS['Navigationszaehler']++;
			echo '#'.$GLOBALS['Navigationszaehler'];
			//if($Navigationszaehler > 1) return false;
			
			// Parameter angegeben?
			if(isset($arrSplit[1]))
			{
				return 'OK';
			}
			else
			{
				// Artikel der aktuellen Seite laden
				$objArtikel = \Database::getInstance()->prepare("SELECT * FROM tl_article WHERE pid = ? AND inColumn = ? AND published = ? ORDER BY sorting ASC")
				                                      ->execute($objPage->id, 'main', 1);
				if($objArtikel->numRows)
				{
					// Aktuelle Artikel-ID laden
					$alias_article = \Input::get('articles');
					$objArticleModel = \ArticleModel::findByIdOrAlias($alias_article);
					if($objArticleModel)
					{
						$aktArtikelID = $objArticleModel->id;
					}
					else $aktArtikelID = 0;

					$nummer = 0;
					while($objArtikel->next())
					{
						$nummer++;
						if($nummer > 1)
						{
							if($objArtikel->id != $aktArtikelID)
							{
								// Artikellink generieren
								$link = '/articles/';
								$link .= (strlen($objArtikel->alias) && !@$GLOBALS['TL_CONFIG']['disableAlias']) ? $objArtikel->alias : $objArtikel->id;
								$url = \Controller::generateFrontendUrl($objPage->row(), $link, null, true);
								$content .= '<a href="'.$url.'">'.$objArtikel->title.'</a> | ';
							}
							else
							{
								$content .= $objArtikel->title.' | ';
							}
						}
						else
						{
							if($aktArtikelID == 0)
							{
								$content .= $objPage->title.' | ';
							}
							else
							{
								// Seitenlink generieren
								$url = \Controller::generateFrontendUrl($objPage->row(), null, null, true);
								$content .= '<a href="'.$url.'">'.$objPage->title.'</a> | ';
							}
						}
					}
				}
				$content = substr($content, 0, -3);
				return $content;
			}
		}
		else
		{
			return false; // Tag nicht dabei
		}

	}
}
