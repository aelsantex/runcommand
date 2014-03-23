<?php
/**
 * Options for the runcommand plugin
 *
 * @author Alessandro Celli <aelsantex@gmail.com>
 */

$meta['rc_debug_level'] = array('multichoice','_choices' => array('0','1','2','3'));
$meta['safe_scripts'] = array('onoff');
$meta['script_dir']  = array('string');
$meta['rc_default_dateformat'] = array('string');
 
