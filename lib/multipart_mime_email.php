<?php

#     Multipart mime email generator library for PHP.
#     Copyright 2002 Jeremy Brand, B.S. <jeremy@nirvani.net>
#     http://www.jeremybrand.com/Jeremy/Brand/Jeremy_Brand.html
# 
#     Multipart mime email generator library for PHP.
#     Release 1.0.0
#     http://www.nirvani.net/software/
# 
#     LICENSE: {{{
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License, Version 2 as
#     published by the Free Software Foundation.
# 
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
# 
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
# }}}
# 
#     ChangeLog: {{{
#       2002/03/31 - Jeremy Brand <jeremy@nirvani.net>
#         * Initial release 
# }}}
# 
#     Prototypes: {{{
#     == PUBLIC functions
#     string mm_read_file(string filename)
#     array  mp_new_message(array message_array)
#       * array[0] returns the message body
#         array[1] returns the string representation of the extra headers needed
#         array[2] returns the array representation of the extra headers needed
# 
#     == PRIVATE functions (you do not need to use these)
#     string mp_new_boundary(void)
#     string mp_new_message_id(void) 
# }}}
# 
#     Usage: {{{
#    
#     rename this file and include it something like this:
#       include('lib_multipart.inc');
# 
#     You need to pass 'mp_new_message' an array for this to proceed.
#     This array of arrays is defined as:
# 
#     # external array
#     $somearray = array (1 => array (),
#                         2 => array (),
#                         ...
#                         N => array ());
# 
#     # internal array... each internal array is ONE attachment (so to speak)
#     array('content_type' => 'content/type',
#           'filename' => 'somefile.ext',
#           'data' => 'string data',
#           'no_base64' => TRUE,
#           'headers' => array());
# 
#     # headers array is defined as...
#     array('header 1' => 'header data 1',
#           'header 2  => 'header data 2',
#            ...
#           'header n' => 'header data n'); 
# }}}
# 
#     SAMPLE ARRAY {{{
# 
# $message[1]['content_type'] = 'text/plain; charset=us-ascii';
# $message[1]['filename'] = '';
# $message[1]['no_base64'] = TRUE;
# $message[1]['data'] = "Hi, how are you doing?\n  sincerely, Me";
# 
# $message[2]['content_type'] = 'text/plain';
# $message[2]['filename'] = '.vimrc';
# $message[2]['data'] = mp_read_file('/home/nobody/.vimrc');
# $message[2]['headers'] = array('X-Sent-By' => 'YourName@planet.mars', 'X-mailer' => 'Fish egg soup 1.0');
# 
# $message[3]['content_type'] = 'image/jpeg';
# $message[3]['filename'] = 'latest.jpg';
# $message[3]['data'] = mp_read_file('/home/nobody/latest.jpg');
# $message[3]['headers'] = array('X-Sent-By' => 'YourName@planet.mars', 'X-mailer' => 'Pine 3.31');
#
# }}}
# 
#     COMPLETE SAMPLE {{{
# 
# 1) Use $message from above.
# 2) The php mail function
# 
# $out = mp_new_message($message);
# mail('to_whom_it_may_concern@planet.mars', 'Your subject', 
#      $out[0], "From: from_who_it_concerned@planet.venus\n".$out[1]);
# 
# }}}
#
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # 

# DEFINES {{{
if (strlen(getenv('HOSTNAME')))
  define('HOSTNAME', getenv('HOSTNAME'));
else
  define('HOSTNAME', 'nohost.nodomain');

# }}}

# public functions
function mp_read_file($filename)/*{{{*/
{
  $buf = '';
  $fd = fopen($filename, 'r');
  if ($fd)
  {
    while(!feof($fd))
    {
      $buf .= fread($fd, 256);
    }
    fclose($fd);
  }
  if (strlen($buf))
    return $buf;
}/*}}}*/

function mp_new_message(&$message_array)/*{{{*/
{
  $boundary = mp_new_boundary();
  while(list(, $chunk) = each($message_array))
  {
    $mess = TRUE;
    unset($headers);
    unset($data);
    if (!$chunk['no_base64'])
    {
      $headers['Content-ID'] = mp_new_message_id();
      $headers['Content-Transfer-Encoding'] = 'BASE64';
      if (strlen($chunk['filename']))
      {
        $headers['Content-Type'] = $chunk['content_type'].'; name="'.$chunk['filename'].'"';
        $headers['Content-Description'] = '';
        $headers['Content-Disposition'] = 'attachment; filename="'.$chunk['filename'].'"';
      }
      else
      {
        $headers['Content-Type'] = $chunk['content_type'];
      }
      $data = chunk_split(base64_encode($chunk['data']),60,"\n");
    }
    else
    {
      $headers['Content-Type'] = $chunk['content_type'];
      $data = $chunk['data'] . "\n";
    }

    if (is_array($chunk['headers']) && count($chunk['headers']))
    {
      while(list($key, $val) = each($chunk['headers']))
      {
        $headers[$key] = $val;
      }
    }

    $buf .= '--' . $boundary. "\n";
    while(list($key, $val) = each($headers))
    {
      $buf .= $key.': '.$val."\n";
    }
    $buf .= "\n";
    $buf .= $data;

  }

  if ($mess)
  {
    $buf .= '--' . $boundary. '--' ;   

      return array 
      (
        0 => $buf,
        1 => 'MIME-Version: 1.0'."\n".
        'Content-Type: MULTIPART/MIXED;'."\n".
          '  BOUNDARY="'.$boundary.'"'."\n".
        'X-Generated-By: Lib Multipart for PHP, Version 1.0.0;'."\n".
          '  http://www.nirvani.net/software/',
        2 => array('MIME-Version: 1.0', 
              'Content-Type: MULTIPART/MIXED;'."\n"
                .'  BOUNDARY="'.$boundary.'"',
              'X-Generated-By: Lib Multipart for PHP, Version 1.0.0;'."\n"
                .'  http://www.nirvani.net/software/')
      );

  }
}/*}}}*/
  
# private functions
function mp_new_message_id()/*{{{*/
{
  return '<'.'lib_multipart-'.str_replace(' ','.',microtime()).'@'.HOSTNAME.'>';
}/*}}}*/

function mp_new_boundary()/*{{{*/
{
  return '-'.'lib_multipart-'.str_replace(' ','.',microtime());
}/*}}}*/

?>