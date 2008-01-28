<!DOCTYPE style-sheet PUBLIC "-//James Clark//DTD DSSSL Style Sheet//EN" [
<!ENTITY dbstyle PUBLIC "-//Norman Walsh//DOCUMENT DocBook HTML Stylesheet//EN" CDATA DSSSL>
]>
<!-- $Id$ -->
<style-sheet>
<style-specification use="docbook">
<style-specification-body>

(define %root-filename% "index")
(define %html-ext% ".html")
(define %use-id-as-filename% #t)
(define %stylesheet% "../images/style.css")
(define %html-header-tags% 
    '(("META" ("HTTP-EQUIV" "Content-Type")("CONTENT" "text/html; charset=UTF-8"))))
(define %section-autolabel% #t)
(define (toc-depth nd)
  (cond ((string=? (gi nd) (normalize "book")) 2)
	((string=? (gi nd) (normalize "set")) 2)
	((string=? (gi nd) (normalize "part")) 2)
	((string=? (gi nd) (normalize "chapter")) 2)
	((string=? (gi nd) (normalize "section")) 2)
	(else 1)))
(define %spacing-paras% #f)
(define %generate-article-toc% #t)
(define %admon-graphics% #t)
(define %admon-graphics-path% "../images/")
(define %body-attr%
(list
    (list "BGCOLOR" "#EBE4D6")
    (list "TEXT" "#000000")))


</style-specification-body>
</style-specification>
<external-specification id="docbook" document="dbstyle">
</style-sheet>
