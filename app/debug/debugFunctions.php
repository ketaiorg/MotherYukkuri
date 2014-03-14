<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * SkypeBot用デバッグ関数
 * デバッグに便利な関数を定義する
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

/**
 * デバッグ用グローバル関数の定義
 */

/**
 * var_dumpショートカット
 * @param mixed $value
 */
function v($value)
{
	var_dump($value);
}

/**
 * var_dump & exitショートカット
 * @param mixed $value
 */
function ve($value)
{
	v($value);
	exit;
}

