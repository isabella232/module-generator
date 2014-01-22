<?php
/*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Generator
{
	/**
	* Resize, cut and optimize image
	*
	* @param string $src_file Image object from $_FILE
	* @param string $dst_file Destination filename
	* @param integer $dst_width Desired width (optional)
	* @param integer $dst_height Desired height (optional)
	* @param string $file_type
	* @return boolean Operation result
	*/
	public static function resize($src_file, $dst_file, $dst_width = 32, $dst_height = 32)
	{
		if (PHP_VERSION_ID < 50300)
			clearstatcache();
		else
			clearstatcache(true, $src_file);
		
		if (!file_exists($src_file) || !filesize($src_file))
			return false;
		list($src_width, $src_height, $type) = getimagesize($src_file);

		$file_type = 'png';

		if (!$src_width)
			return false;
		if (!$dst_width)
			$dst_width = $src_width;
		if (!$dst_height)
			$dst_height = $src_height;

		$src_image = ImageManager::create($type, $src_file);

		$width_diff = $dst_width / $src_width;
		$height_diff = $dst_height / $src_height;

		if ($width_diff > 1 && $height_diff > 1)
		{
			$next_width = $src_width;
			$next_height = $src_height;
		}
		else
		{
			$next_height = $dst_height;
			$next_width = round(($src_width * $next_height) / $src_height);
			$dst_width = (int)(!Configuration::get('PS_IMAGE_GENERATION_METHOD') ? $dst_width : $next_width);
		}

		if (!ImageManager::checkImageMemoryLimit($src_file))
			return false;
		
		$dest_image = imagecreatetruecolor($dst_width, $dst_height);

		imagealphablending($dest_image, false);
		imagesavealpha($dest_image, true);
		$transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
		imagefilledrectangle($dest_image, 0, 0, $dst_width, $dst_height, $transparent);

		imagecopyresampled($dest_image, $src_image, (int)(($dst_width - $next_width) / 2), (int)(($dst_height - $next_height) / 2), 0, 0, $next_width, $next_height, $src_width, $src_height);
		return (ImageManager::write($file_type, $dest_image, $dst_file));
	}

	/**
	 * Remplace contents
	 *
	 * @param array $sourceDest Array with the key as the value to replace the value as content
	 * @param string $file File in which we will make replacement
	 * @return void
	 */
	public static function replaceVar(array $sourceDest, $file) {
		$template = strtr(file_get_contents($file), $sourceDest);
		file_put_contents($file, $template);
		unset($template, $sourceDest, $file);
	}

	/**
	 * Copy a file or recursively copy a directories contents
	 *
	 * @param string $source The path to the source file/directory
	 * @param string $dest The path to the destination directory
	 * @return void
	 */
	public static function copyRecursive($source, $dest)
	{
		if (is_dir($source))
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ($iterator as $file)
			{
				if ($file->isDir()) {
					if(!is_dir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName()))
						mkdir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
				}
				else
					copy($file, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
			}
			unset($iterator, $file);
		}
		else
			copy($source, $dest);
	}

	/**
	* Delete a file/recursively delete a directory
	*
	* NOTE: Be very careful with the path you pass to this!
	*
	* @param string $path The path to the file/directory to delete
	* @return void
	*/
	public static function deleteRecursive($path)
	{
		if (is_dir($path))
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($iterator as $file)
			{
				if ($file->isDir())
					rmdir($file->getPathname());
				else
					unlink($file->getPathname());
			}
			unset($iterator, $file);
			rmdir($path);
		}
		else
			unlink($path);
	}

	public static function standardTPL($moduleName, $hookTPL, $val)
	{
		return "if (".'$'."this->isCached('$moduleName$val.tpl', ".'$'."this->getCacheId()) === false)
		{
			".'$'."this->smarty->assign(array(

			));
		}

		// Clean memory
		unset(".'$'."params);

		return ".'$'."this->display(__FILE__, '$hookTPL$moduleName$val.tpl', ".'$'."this->getCacheId());";
	}
}