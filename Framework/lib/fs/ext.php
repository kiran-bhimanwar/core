<?php
/**
 * 2015-11-28 http://stackoverflow.com/a/10368236
 * @used-by df_asset_create()
 * @used-by df_file_ext_def()
 * @param string $f
 * @return string
 */
function df_file_ext($f) {return pathinfo($f, PATHINFO_EXTENSION);}

/**
 * 2018-07-06
 * @used-by df_report()
 * @param string $f
 * @param string $ext
 * @return string
 */
function df_file_ext_def($f, $ext) {return ($e = df_file_ext($f)) ? $f : df_trim_right($f, '.') . ".$ext";}

/**
 * 2015-04-01
 * Раньше алгоритм был таким: return preg_replace('#\.[^.]*$#', '', $file)
 * Новый вроде должен работать быстрее?
 * http://stackoverflow.com/a/22537165
 * 2019-08-09
 * 1) preg_replace('#\.[^.]*$#', '', $file) preserves the full path.
 * 2) pathinfo($file, PATHINFO_FILENAME) strips the full path and returns the base name only.
 * @used-by wolf_u2n()
 * @used-by \Justuno\M2\Controller\Js::execute()
 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @param string $s
 * @return mixed
 */
function df_strip_ext($s) {return preg_replace('#\.[^.]*$#', '', $s);}