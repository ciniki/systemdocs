<?php
//
// Description
// -----------
// This function will process a block of text markdown content into 
// html content.  This is a very simplistic form of markdown, and does
// not implement the complete specification.
//
// You can find more information on markdown at: http://daringfireball.net/projects/markdown/
//
// Arguments
// ---------
// ciniki:          
// content:         The content containing the markdown which needs to be processed.
// 
// Returns
// -------
// <rsp stat="ok" html_content="html content" />
//
function ciniki_systemdocs_processMarkdown($ciniki, $content) {

    //
    // Escape any existing html
    //
    $content = htmlspecialchars($content);

    //
    // Break into lines
    //
    $lines = explode("\n", $content);

    //
    // Check for lists
    //
    $prev_line = '';
    $list_level = 0;
    $list_type = '';
    $paragraph = 0;
    foreach($lines as $lnum => $line) {
        // Check for list item
        // or for hex number or number unordered list
        // 1 - The first number
        if( preg_match('/^\s*[+-]\s*(.*)$/', $line, $matches) ) {
            if( $list_level == 0 ) {
                if( $paragraph > 0 ) {
                    $lines[$lnum-1] .= "</p>";
                    $paragraph = 0;
                }
                $lines[$lnum] = "<ul>\n" . '<li>' . $matches[1];
                $list_level++;
                $list_type = 'ul';
            } else {
                $lines[$lnum] = '<li>' . $matches[1];
            }
        }
        // 0x01 - The first flag
        elseif( preg_match('/^\s*((0x[0-9]+|[0-9]+)\s*-\s*(.*))$/', $line, $matches) ) {
            if( $list_level == 0 ) {
                if( $paragraph > 0 ) {
                    $lines[$lnum-1] .= "</p>";
                    $paragraph = 0;
                }
                $lines[$lnum] = "<dl>\n" . '<dt>' . $matches[2] . '</dt><dd>' . $matches[3];
                $list_level++;
                $list_type = 'dl';
            } else {
                $lines[$lnum] = '</dd><dt>' . $matches[2] . '</dt><dd>' . $matches[3];
            }
        }
        // Definition lists
        // Label text :: The first item in the list
        elseif( preg_match('/^\s*((.*)\s::\s(.*))$/', $line, $matches) ) {
            if( $list_level == 0 ) {
                if( $paragraph > 0 ) {
                    $lines[$lnum-1] .= "</p>";
                    $paragraph = 0;
                }
                $lines[$lnum] = "<dl>\n" . '<dt>' . $matches[2] . '</dt><dd>' . $matches[3];
                $list_level++;
                $list_type = 'dl';
            } else {
                $lines[$lnum] = '</dd><dt>' . $matches[2] . '</dt><dd>' . $matches[3];
            }
        }
        // Check for tables
        // Check for text line
        elseif( preg_match('/^\s*((.*)\s\|\s\s*(.*))$/', $line, $matches) ) {
            if( $list_level == 0 ) {
                if( $paragraph > 0 ) {
                    $lines[$lnum-1] .= "</p>";
                    $paragraph = 0;
                }
                $lines[$lnum] = '<table><tr><td>' . $matches[2] . '</td><td>' . $matches[3];
                $list_level++;
                $list_type = 'table';
            } else {
                $lines[$lnum] = '</td></tr><tr><td>' . $matches[2] . '</td><td>' . $matches[3];
            }
        }
        elseif( $paragraph == 0 && $list_level == 0 && preg_match('/[a-zA-Z0-9]/', $line) ) {
            $lines[$lnum] = '<p>' . $line;
            $paragraph = 1;
        }
        // Check for blank line, end of list
        elseif( $list_level > 0 && preg_match('/^\s*$/', $line) ) {
            if( $list_type == 'dl' ) {
                $lines[$lnum] = '</dd></dl>';
            } elseif( $list_type == 'table' ) {
                $lines[$lnum] = '</td></tr></table>';
            } else {
                $lines[$lnum] = '</ul>';
            }
            $list_level = 0;
        }
        // Check for blank line, end of paragraph
        elseif( $paragraph > 0 && preg_match('/^\s*$/', $line) ) {
            $lines[$lnum] = '</p>';
            $paragraph = 0;
        }
        //
        // Check for emphasis
        // *em*, or **strong**
        //
        $lines[$lnum] = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $lines[$lnum]);
        $lines[$lnum] = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $lines[$lnum]);
    }
    if( $list_level > 0 ) {
        if( $list_type == 'dl' ) {
            $lines[$lnum+1] = '</dd></dl>';
        } elseif( $list_type == 'table' ) {
            $lines[$lnum+1] = '</td></tr></table>';
        } else {
            $lines[$lnum+1] = '</ul>';
        }
    }
    elseif( $paragraph > 0 ) {
        $lines[$lnum+1] = '</p>';
    }

    $html_content = implode("\n", $lines);
    
    //
    // Check for URL's
    //
    // URL's with a title
    $html_content = preg_replace('/\[([^\]]+)\]\((http[^\)]+)\)/', '<a target="_blank" href="$2">$1</a>', $html_content);
    // URL's without a title
    $html_content = preg_replace('/([^\"])(http[^ ]+)/', '$1<a target="_blank" href="$2">$2</a>', $html_content);

    return array('stat'=>'ok', 'html_content'=>$html_content);
}
?>
