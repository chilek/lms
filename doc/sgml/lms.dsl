<!DOCTYPE style-sheet PUBLIC "-//James Clark//DTD DSSSL Style Sheet//EN" [
<!ENTITY dbstyle PUBLIC "-//Norman Walsh//DOCUMENT DocBook HTML Stylesheet//EN" CDATA DSSSL>
]>
<!--
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;  Konwersjê do HTML wykonuje polecenie:
;  jade -t sgml -d lms.dsl index.sgml 
;  lub:
;  jade -u -t sgml -d lms.dsl index.html > index.html
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
-->
<style-sheet>
<style-specification use="docbook">
<style-specification-body>

(define %root-filename% "index")
(define %html-ext% ".html")
(define %use-id-as-filename% #t)
(define %stylesheet% "style.css")
(define %html-header-tags% 
    '(("META" ("HTTP-EQUIV" "Content-Type")("CONTENT" "text/html; charset=ISO-8859-2"))))
(define %html40% #t)
(define %section-autolabel% #t)
(define (toc-depth nd)
  (cond ((string=? (gi nd) (normalize "book")) 2)
	((string=? (gi nd) (normalize "set")) 2)
	((string=? (gi nd) (normalize "part")) 2)
	((string=? (gi nd) (normalize "chapter")) 2)
	((string=? (gi nd) (normalize "section")) 2)
	(else 1)))
(define %spacing-paras% #f)

</style-specification-body>
</style-specification>
<external-specification id="docbook" document="dbstyle">
</style-sheet>