<?php

/**
 * # file index
 * works for subdirectories and navigation, not just a single directory!
 * It could probably be less confusing but it works. for me at least.
 * It's probably not safe for a public site, its just for dev environment.
 */
# print file_index();
function file_index(array$input = [], $wrap_html = true)
{
    if (empty($input)) {
        $input = $_GET;
    }

    $base = isset($input['base'])
        ? strtr($input['base'], '\\', '/')
        : '.';
    $dirs = glob($base.'/*', GLOB_ONLYDIR);
    $files = array_diff(glob($base.'/*'), $dirs);
    natcasesort($files);
    $baselen = strlen($base);

    # get previous directory from current base (prev = ..)
    $prev = explode('/', './'. ltrim($base, './'));
    if (count($prev)) {
        array_pop($prev);
    }
    $prev = join('/', $prev);

    # If base was empty prev should be too
    if (!$base || $base == '.') {
        $prev = '';
    }

    # Link to the base
    $bl = '?base='. $base;

    # Now transform base to empty or add trailing slash
    $base = ('.' == $base)
        ? ''
        : $base .'/';

    # get sort method from input
    $sm = isset($input['sort'])
        ? (string) $input['sort']
        : 0;

    # get files found
    $ff = [];
    foreach ($files as $file) {
        $ff[] = [
            'name' => ($name = ltrim(substr($file, $baselen), '/')),
            'type' => substr($name, strrpos($name, '.') + 1),
            'mod' => filemtime($file),
            'size' => filesize($file),
        ];
    }

    # define a function to sort items by column (name, type, size, mod)
    $sort = function($items, $col) {
        if(!$col) {
            return $items;
        }
        $temp = $out = [];
        foreach ($items as $k => $v) {
            $temp[$k] = strtolower($v[$col]);
        }
        asort($temp);
        foreach ($temp as $k => $v) {
            $out[] = $items[$k];
        }
        return $out;
    };

    # define a function to get the file size in human-readable form
    $format = function($z) {
        /** @link http://stackoverflow.com/questions/2510434 */
        $u = ['B', 'KiB', 'MiB', 'GiB', 'TiB']; # units
        $p = min(floor(\log($b = max($z, 0) ?: 0) / \log(1024)), count($u) - 1);
        $b = round($b /= (pow(1024, $p) > 0 ?: 1), 2); # bytes
        return sprintf('%-7.2f %s', $b, $u[$p]);
    };

    # get self filename
    $self = basename(getenv('SCRIPT_FILENAME'));

    # If we're wrapping html (to use as an index file) get the html ready
    $out = (!$wrap_html) ? '': '
        <!doctype html>
        <html>
        <head>
        <meta charset="utf-8">
        <title>'. ($base ? $base.' - ' : ' / - ') .' - Directory Index</title>
        <style>
            html { font-family:"Segoe UI", "Droid Sans", sans-serif; }
            body { margin:2% auto; width:85%; }
            pre, code { font-size:100%; }
            a { text-decoration:none; }
            th { text-align:left; padding:0.5em 0; }
            a, h1 a:visited, .header a:visited { color:#11a; display:inline; }
            a:hover { text-decoration:underline; color:#006; }
            a:visited { color:purple; }
            h1 { padding:0.5em 0; margin:0; font-size:110%; }
            table { width:100%; border-collapse:collapse; }
            td { padding:0.2em 0.5em; border:1px solid #eee; }
            tr:not(.header):hover { background:#eee; }
            table.files td { width:33%; white-space:nowrap; }
        </style>
        </head>
        <body class="directoryindex">
    ';

    $out .= '
        <table class="dirs">
        <tr class="header">
        <th><h1><a href="'. $self .'">Document Root</a></h1></th>
        </tr>
        <tr class="header">
        <th><b>Directories</b>:</th>
        </tr>
    ';

    # If there is a previous directory then link to it as `..`
    if ($prev) {
        $out .= '<tr><td><a href="'. $self .'?base='. $prev .'">..</a></td></tr>';
    }

    foreach ($dirs as $dir) {
        $dir = trim(substr($dir, strlen($base)), './'); # format dir name
        $out.= '
            <tr><td>
            <a href="'. $self .'?base='. $base . $dir .'">'. $dir .'</a>
            </td></tr>
        ';
    }

    # Add the header table with sort method links
    $out .= '
        </table>
        <table class="files">
        <tr class="header sort">
        <th><a href="'. $self . $bl .'&sort='. ($sm == 'name' ? 'r' : '') .'name">Filename:</a></th>
        <th><a href="'. $self . $bl .'&sort='. ($sm == 'type' ? 'r' : '') .'type">Type:</a></th>
        <th><a href="'. $self . $bl .'&sort='. ($sm == 'mod' ? 'r' : '') .'mod">Modified:</a></th>
        <th><a href="'. $self . $bl .'&sort='. ($sm == 'size' ? 'r' : '') .'size">Size:</a></th>
        </tr>
    ';

    # Sort and display the files found by the sort method
    $sorted = ($sm[0] == 'r')
        ? array_reverse($sort($ff, substr($sm, 1)))
        : $sort($ff, $sm);

    foreach ($sorted as $file) {
        $out .= '
            <tr>
            <td><a href="'. $base . $file['name'] .'">'. $file['name'] .'</a></td>
            <td>'. strtoupper($file['type']) .'</td>
            <td>'. date('r',$file['mod']) .'</td>
            <td><code>'. $format($file['size']) .'</code></td>
            </tr>
        ';
    }

    return $out. '</table>'. ($wrap_html ? '</body></html>' : '');
}
