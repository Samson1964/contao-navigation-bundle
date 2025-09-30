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
		$arrSplit = explode('::', $strTag);

		// Inserttag {{artikelnavigation::id}}
		// Liefert zur aktuellen bzw. gewünschten (id) Seite eine Artikelnavigation
		if($arrSplit[0] == 'artikelnavigation' || $arrSplit[0] == 'artikelnavigation_alter')
		{
			// Parameter angegeben?
			if(isset($arrSplit[1]))
			{
				return 'OK';
			}
			else
			{
				return 'Geburtstag fehlt!';
			}
		}
		else
		{
			return false; // Tag nicht dabei
		}

	}
}
