<?php

/**
 * List files in a folder recursively with the option to browse.
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category
 * @package     File_Browser
 * @author      Gabriel Dunne <gdunne[at]quilime[dot]com>
 * @copyright   2006 Gabriel Dunne
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://quilime.com/library/php/file-browser/
 */


class File_Browser
{

var $p = '';
var $root = './';
var $webroot = '';
var $files = array();
var $images = array();
var $audio = array();
var $parent_dir = false;
var $thumbs = true;
var $inline_audio = true;
var $readme_file = 'README';
var $max_image_width = 200;


function File_Browser($params = array())
{
    $this->p = $this->validate_path($_GET['p']);
    $this->root  = isset($params['root']) ? $params['root'] : $this->root;
    $this->title = isset($params['title']) ? $params['title'] : 'File Browser';
    $this->webroot = isset($params['webroot']) ? $params['webroot'] : '';
    $this->readme_file = isset($params['readme']) ? $params['readme'] : $this->readme_file;
    $this->thumbs_file = isset($params['thumbs_file']) ? $params['thumbs_file'] : '.thumbs';


    if( is_dir($this->root . $this->p) ) {
        $allFiles = $this->fileArray($this->root . $this->p, array(), array());

        $files = array();
        $folders = array();
        $images = array();
        $audio = array();

        foreach($allFiles as $f) {
            $f['name'] = $f['type'] == "Folder" ? $f['name'] . '/' : $f['name'];
            $f['title'] = $f['name'];
            $f['href'] = $f['type'] == "Folder" ? "?p=" . $this->p . $f['name'] : $this->root . $this->p . $f['name'];
            $f['class'] = $f['type'] == "Folder" ? 'dir' : $f['name'];

            if($f['type'] == "Folder")
                $folders[] = $f;
            else
                $files[] = $f;

            if ( $this->thumbs &&
               ( strtolower($f['type']) == 'jpg' ||
                 strtolower($f['type']) == 'png' ||
                 strtolower($f['type']) == 'jpeg' ||
                 strtolower($f['type']) == 'gif') )
            {
                $f['imagesize'] = getimagesize($this->root . $this->p . $f['name']);

                // create thumbnail with convert
                $thumb_dims = array(200, 200);



                $tmp_image = $this->root . $this->p . $f['name'];
                $thumb = '.t/' . $this->p . $f['name'];

                if(!file_exists($thumb)) {

                    $this->mk_dir('.t/' . $this->p);

                //* resize centered on white
                $exec = "/usr/bin/convert \"$tmp_image\" -resize $thumb_dims[0]x$thumb_dims[1]\> \
                                  -size $thumb_dims[0]x$thumb_dims[1] xc:white +swap -gravity center  -composite \
                                   \"$thumb\"";
                //*/

                exec($exec, $retval);

                }

                $f['thumb'] = $thumb;
                $images[] = $f;
            }

            if ( $this->inline_audio &&
               ( strtolower($f['type']) == 'mp3' ||
                 strtolower($f['type']) == 'wav') )
            {
                $audio[] = $f;
            }

        }
        $this->files = $allFiles = array_merge($folders, $files);
        $this->images = $images;
        $this->audio = $audio;
    }

    $this->breadcrumbs  = '<li><a href="' . $this->webroot . '">' . $this->title . '</a>/</li>';
    $this->breadcrumbs .= $this->breadcrumbs( $this->p );
    $this->logo = isset($params['logo']) ? '<img src="' . $params['logo'] . '" />' : false;
    $this->parent_dir = $this->p ? pathinfo($this->p, PATHINFO_DIRNAME) . '/' : false;


    switch($_GET['format'])
    {
        case 'xspf' :
            exit;
            break;

        default :
            break;
    }
}



//recursively creates a folder.
function mk_dir($path, $rights = 0777) {//{{{
  //$folder_path = array(strstr($path, '.') ? dirname($path) : $path);
  if (!@is_dir($path)) {
    $folder_path = array($path);
  } else {
    return;
  }

  while(!@is_dir(dirname(end($folder_path)))
         && dirname(end($folder_path)) != '/'
         && dirname(end($folder_path)) != '.'
         && dirname(end($folder_path)) != '')
  {
    array_push($folder_path, dirname(end($folder_path)));
  }

  while($parent_folder_path = array_pop($folder_path)) {
    if(!@mkdir($parent_folder_path, $rights)) {
      user_error("Can't create folder \"$parent_folder_path\".\n");
    }
  }
}//}}}




/**
 *  Render HTML
 */
function html()
{
    if(is_file($this->root . $this->p . $this->readme_file)) {
        echo '<pre id="readme">';
        include($this->root . $this->p . $this->readme_file);
        echo '</pre>';
    }

    //
    // IMAGES
    //
    if($this->images) {
    echo '<ol id="media">';
    foreach($this->images as $i) {
        $style = '';
        $sm = false;
        if( $i['imagesize'][0] < $this->max_image_width) {
            $sm = true;
            $img_w = $i['imagesize'][0];
        }
        ?>
        <li <? if($sm) echo 'style="width:'.($img_w+20).'px;"'; ?>>
            <a title="<?=$i['title']?>" href="<?=$i['href']?>">
            <div>
            <img <? if($sm) echo 'style="width:'.($img_w).'px;"'; ?> src="<? echo $i['thumb'] ?>" class="im" />
            </div>
            <span><?=$i['name'];?></span>
            </a>
        </li>
    <?
    }
    echo '<div style="clear:both;"></div>';
    echo '</ol>';
    }

    //
    // LISTEN
    //
    if ($this->audio) {
    ?>
        <script type="text/javascript">
            function listen (node, src)
            {
                var ins = '<embed height=16px src="' + src + '" autostart=false loop=false>';
                node.parentNode.innerHTML = ins;
            }
        </script>
    <?
    }


    //
    // FILES
    //
    if($this->files) {

    echo '<ol id="list">';
    foreach($this->files as $f)
    {
        ?>
        <li class="<?=$f['class'] ?> <?=$f['type']?>">
            <a title="<?=$f['title']?>" href="<?=$f['href']?>">
                <span><?=$f['name'];?></span>
            </a>
                <? if ($f['type'] == 'mp3') : ?>
                    <span class="audio">
                    <a href="#" onClick="listen(this, '<?=$f['href']?>');">listen</a>
                    </span>
                <? endif; ?>
        </li>
        <?
    }
    echo '</ol>';

    echo $this->parent_dir ? '<div class="parent"><a title="parent dir" href="?p=' . $this->parent_dir . '">&larr; back</a></div>' : '';

    }
    else {
        ?>
        <p>
        </p>
        <?
    }
}


function validate_path($path)
{
    if (  isset($path) &&
          !strstr($path, '../') &&
          $path != "./" &&
          $path != "//" &&
          is_dir($this->root . $path))
        return $path;
    else
        return '';
}



/**
 *  file/folder array
 *
 *  @param String $p                the path
 *  @param String[] $extensions     extensions of files to show
 *  @param String[] $excludes       files to exclude
 *  @param Bool     $show_hidden    show hidden files
 *  @param Array[]                  the array of files
 *
 */
function fileArray($p, $extensions = Array(), $excludes = Array('.', '..'), $show_hidden = false)
{
    $result = Array();
    $parsedResult = Array();
    if ($handle = opendir($p)) {
        while (false !== ($file = readdir($handle))) {
            if (!in_array($file, $excludes))
            {
                if(!$show_hidden)
                if( $file{0} == "." || $file{0} == "~" || $file{0} == "#") continue;
                // is folder
                if(is_dir($p . $file)) {
                    $folderInfo  = Array (
                        'name'      => $file,
                        'mtime'     => $mtime = filemtime($p . $file),
                        'type'      => 'Folder',
                        'size'      => null );
                    $result[] = $folderInfo;
                }
                // is file
                else {
                    $fileInfo  = Array(
                        'name'      => $file,
                        'mtime'     => $mtime = filemtime($p . $file),
                        'type'      => $type  = pathinfo($file, PATHINFO_EXTENSION),
                        'size'      => filesize($p . $file));
                    $result[] = $fileInfo;
                }
            }
        }
    }
    return $this->sort_array_of_arrays($result, 'name', 'name');
}


/**
 *  sort array of arrays
 *  requires array of associative arrays -- not checked within array
 *
 *  @author : http://phosphorusandlime.blogspot.com/2005/12/php-sort-array-by-one-field-in-array.html
 *
 *  @param Array[] $ARRAY           (two dimensional array of arrays)
 *  @param String $sortby_index     index/column to be sorted on
 *  @param String $key_index        equivalent to primary key column in SQL
 */
function sort_array_of_arrays($ARRAY, $sortby_index, $key_index)
{
    $ORDERING = array();
    $SORTED = array();
    $_DATA = array();
    $_key = '';
    $_sort_col_val = '';
    $_i = 0;

    foreach ( $ARRAY as $_DATA ) {
        $_key = $_DATA[$key_index];
        $ORDERING[$_key] = $_DATA[$sortby_index];
    }
    asort($ORDERING);

    foreach ( $ORDERING as $_key => $_sort_col_val ) {
        foreach ($ARRAY as $_i => $_DATA) {
            if ( $_key == $_DATA[$key_index] ) {
                $SORTED[] = $ARRAY[$_i];
                continue;
            }
        }
    }
    return $SORTED;
}


/**
 *  breadcrumbs
 *  @param String $path         path
 *  @param String $sep          separator
 *  @param String $path_var     path variable for the url
 *  @return String
 */
function breadcrumbs($path, $sep = "/", $path_var = "p")
{
    $pathParts = explode("/", $path);
    $pathCT = 0;
    $br = "";
    foreach($pathParts as $pt) {
        $br .= '<li><a href="?' . $path_var . '=';
        for($i=0; $i <= $pathCT; $i++) {
            $br .= $pathParts[$i] . $sep;
        }
        $br .= '">'.$pt.'</a>';
        if($pathCT < sizeof($pathParts)-2)
            $br .= $sep;
        $br .= '</li>';
        $pathCT++;
    }
    return $br;
}

}

?>