<?php
// +-----------------------------------------------------------------------+
// | PhpWebGallery - a PHP based picture gallery                           |
// | Copyright (C) 2002-2003 Pierrick LE GALL - pierrick@phpwebgallery.net |
// | Copyright (C) 2003-2008 PhpWebGallery Team - http://phpwebgallery.net |
// +-----------------------------------------------------------------------+
// | file          : $Id$
// | last update   : $Date$
// | last modifier : $Author$
// | revision      : $Revision$
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

/**
 * This file is included by the main page to show thumbnails for the default
 * case
 *
 */

$page['rank_of'] = array_flip($page['items']);

$pictures = array();

$selection = array_slice(
  $page['items'],
  $page['start'],
  $page['nb_image_page']
  );

if (count($selection) > 0)
{
  $query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', $selection).')
;';
  $result = pwg_query($query);
  while ($row = mysql_fetch_assoc($result))
  {
    $row['rank'] = $page['rank_of'][ $row['id'] ];

    array_push($pictures, $row);
  }

  usort($pictures, 'rank_compare');
}

if (count($pictures) > 0)
{
  // define category slideshow url
  $row = reset($pictures);
  $page['cat_slideshow_url'] =
    add_url_params(
      duplicate_picture_url(
        array(
          'image_id' => $row['id'],
          'image_file' => $row['file']
        ),
        array('start')
      ),
      array('slideshow' =>
        (isset($_GET['slideshow']) ? $_GET['slideshow']
                                   : '' ))
    );

  if ($user['show_nb_comments'])
  {
    $query = '
SELECT image_id, COUNT(*) AS nb_comments
  FROM '.COMMENTS_TABLE.'
  WHERE validated = \'true\'
    AND image_id IN ('.implode(',', $selection).')
  GROUP BY image_id
;';
    $nb_comments_of = simple_hash_from_query($query, 'image_id', 'nb_comments');
  }
}

// template thumbnail initialization
$template->set_filenames( array( 'index_thumbnails' => 'thumbnails.tpl',));

trigger_action('loc_begin_index_thumbnails', $pictures);

foreach ($pictures as $row)
{
  $thumbnail_url = get_thumbnail_url($row);

  // link on picture.php page
  $url = duplicate_picture_url(
        array(
          'image_id' => $row['id'],
          'image_file' => $row['file']
        ),
        array('start')
      );

  $tpl_var =
    array(
      'ID'            => $row['id'],
      'IMAGE'         => $thumbnail_url,
      'IMAGE_ALT'     => $row['file'],
      'IMAGE_TITLE'   => get_thumbnail_title($row),
      'IMAGE_TS'      => get_icon($row['date_available']),
      'U_IMG_LINK'    => $url,
    );

  if ($user['show_nb_hits'])
  {
    $tpl_var['NB_HITS'] = $row['hit'];
  }

  if ($conf['show_thumbnail_caption'])
  {
    // name of the picture
    if (isset($row['name']) and $row['name'] != '')
    {
      $name = $row['name'];
    }
    else
    {
      $name = str_replace('_', ' ', get_filename_wo_extension($row['file']));
    }

    switch ($page['section'])
    {
      case 'best_rated' :
      {
        $name = '('.$row['average_rate'].') '.$name;
        break;
      }
      case 'most_visited' :
      {
        if ( !$user['show_nb_hits']) {
          $name = '('.$row['hit'].') '.$name;
        }
        break;
      }
    }

    $tpl_var['ELEMENT_NAME'] = $name;
  }

  if ( isset($nb_comments_of) )
  {
    $row['nb_comments'] = isset($nb_comments_of[$row['id']])
        ? (int)$nb_comments_of[$row['id']] : 0;
    $tpl_var['NB_COMMENTS'] = $row['nb_comments'];
  }

  //plugins need to add/modify sth in this loop ?
  $tpl_var = trigger_event('loc_index_thumbnail', $tpl_var, $row);

  $template->append('thumbnails', $tpl_var);
}

trigger_action('loc_end_index_thumbnails', $pictures);
$template->assign_var_from_handle('THUMBNAILS', 'index_thumbnails');

pwg_debug('end include/category_default.inc.php');
?>
