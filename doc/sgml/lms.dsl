<!doctype style-sheet PUBLIC "-//James Clark//DTD DSSSL Style Sheet//EN">

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; lms.dsl      - Szablon do konwersji z DocBook do HTML dla lms bazowany na:
; dbtohtml.dsl - DSSSL style sheet for DocBook to HTML conversion (jadeware)
;
; Author 	  : Marcin Król (lexx@polarnet.gliwice.pl)
;
; Oryginal Author : Mark Burton (markb@ordern.com)
; Created On      : Fri Jun 13 18:21:14 1997
; Last Modified By: Mark Burton
; Last Modified On: Tue Jun 24 11:39:23 1997
;
; $Id$
;
;
; Use this style sheet in conjunction with splithtml.pl to produce a separate
; file for each Chapter and Appendix. Perhaps like this:
;
; jade -d dbtohtml.dsl -t sgml < yourdoc.sgm | splithtml.pl yourdoc

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; declare non-standard functions

(declare-flow-object-class element
  "UNREGISTERED::James Clark//Flow Object Class::element")
(declare-flow-object-class empty-element
  "UNREGISTERED::James Clark//Flow Object Class::empty-element")
(declare-flow-object-class document-type
  "UNREGISTERED::James Clark//Flow Object Class::document-type")
(declare-flow-object-class processing-instruction
  "UNREGISTERED::James Clark//Flow Object Class::processing-instruction")
(declare-flow-object-class entity
  "UNREGISTERED::James Clark//Flow Object Class::entity")
(declare-flow-object-class entity-ref
  "UNREGISTERED::James Clark//Flow Object Class::entity-ref")
(declare-flow-object-class formatting-instruction
  "UNREGISTERED::James Clark//Flow Object Class::formatting-instruction")
(declare-characteristic preserve-sdata?
 "UNREGISTERED::James Clark//Characteristic::preserve-sdata?" #f)

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; variables

(define %no-split-output% #f)
(define %no-make-toc% #f)
(define %no-make-index% #t)
(define %no-shade-screen% #f)
(define %html-public-id% "-//W3C//DTD HTML 3.2 Final//EN")
(define %output-basename% "DOCPART")
(define %output-suffix% ".html")


;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; top-level sections

(element BOOK
  (cond (%no-split-output%
         (make sequence
           (make document-type
             name: "html"
             public-id: %html-public-id%)
           (make element gi: "HTML"
                 (make sequence
                   (make element
                     gi: "HEAD"
                     (make element
                       gi: "TITLE"
                       (with-mode extract-title-text
                         (process-first-descendant "TITLE"))))
                   (make element
                     gi: "BODY"
                     (make sequence
                       (process-children)
                       (cond ((not %no-make-index%)
                              (make sequence
                                ;;(make-fat-rule)
                                (make-index)))
                             (#t (empty-sosofo)))))))))
        (#t
         (make sequence
           (make-split-file (string-append %output-basename%
                                           %output-suffix%)
                            (make sequence
                              ;; Po co tutaj tytu³
                              ;; (process-first-descendant "TITLE")
                              (process-first-descendant "BOOKINFO")))
           (process-matching-children "PREFACE" "CHAPTER" "APPENDIX")
           (cond ((not %no-make-index%)
                  (make-split-file (string-append %output-basename%
                                                  "-INDEX"
                                                  %output-suffix%)
                                   (make-index)))
                 (#t (empty-sosofo)))))))

(define (make-split-file file-name content)
  (make sequence
    (make formatting-instruction
      data: (string-append "<" "!-- " "Start of " file-name " -->"))
    (make document-type
      name: "html"
      public-id: %html-public-id%)
    (make element gi: "HTML"
          (make sequence
            (make element
              gi: "HEAD"
	      (make empty-element 
		gi: "LINK" attributes: '(("href" "style.css")("rel" "stylesheet")("type" "text/css"))
              (make element
                gi: "TITLE"
                (with-mode extract-title-text
                  (process-first-descendant "TITLE"))))
            (make element
              gi: "BODY"  attributes: '(("bgcolor" "#EBE4D6"))
              (make sequence
                (make-anchor)
                content))))
    (make formatting-instruction
      data: (string-append "<" "!-- " "End of " file-name " -->"))))

(define (make-pref-chap-app)
  (cond (%no-split-output%
         (make sequence
           (make-anchor)
           ;;(make-fat-rule)
           (process-children)))
        (#t
         (make-split-file (link-file-name (current-node)) (process-children)))))

(element PREFACE
  (make-pref-chap-app))

(element CHAPTER
  (make-pref-chap-app))

(element APPENDIX
  (make-pref-chap-app))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; sections

(element SECT1
  (make sequence
    (make-anchor)
    (process-children)))

(element SECT2
  (make sequence
    (make-anchor)
    (process-children)))

(element SECT3
  (make sequence
    (make-anchor)
    (process-children)))

(element SECT4
  (make sequence
    (make-anchor)
    (process-children)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; titles

(mode extract-title-text
  (element (TITLE)
    (process-children)))

(element (BOOK TITLE)
  (make sequence
    (make element
      gi: "CENTER"
      (make element
        gi: "H1"
        (process-children)))))

(element (CHAPTER TITLE)
  (make element gi: "H1"
        (make sequence
          (literal (chap-app-head-label "Chapter"))
          (process-children-trim))))

(element (PREFACE TITLE)
  (make element gi: "H1"
        (make sequence
          (literal (chap-app-head-label "Preface"))
          (process-children-trim))))

(element (APPENDIX TITLE)
  (make element gi: "H1"
        (make sequence
          (literal (chap-app-head-label "Appendix"))
          (process-children-trim))))

(element (SECT1 TITLE)
  (make element gi: "H2"))

(element (SECT2 TITLE)
  (make element gi: "H3"))

(element (SECT3 TITLE)
  (make element gi: "H4"))

(element (SECT4 TITLE)
  (make element gi: "H5"))

(element (EXAMPLE TITLE)
  (make element gi: "B"))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; font changers

(element EMPHASIS
  (make element gi: "I"))

(element TYPE
  (make element gi: "B"
        (make element gi: "TT")))

(element TOKEN
  (make element gi: "I"
        (make element gi: "B"
              (make element gi: "TT"))))

(element REPLACEABLE
  (make element gi: "I"))

(element FIRSTTERM
  (make element gi: "I"))

(element APPLICATION
  (make element gi: "TT"))

(element FILENAME
  (make element gi: "TT"))

(element LITERAL
  (make element gi: "TT"))

(element ENVAR
  (make element gi: "TT"))

(element SUBSCRIPT
  (make element gi: "SUB"))

(element SUPERSCRIPT
  (make element gi: "SUP"))

(element CITETITLE
  (make element gi: "I"))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; paragraph like things

(element WARNING
  (make-warning-para "Uwaga!:"))

(element EXAMPLE
  (make element gi: "P"))

(element NOTE
  (make-special-para "Notatka:"))

(element COPYRIGHT
  (make sequence
   (make empty-element gi: "P")
   (make element gi: "B"
    (literal "(c)"))
   (process-children)))
  
(element PARA
  (make sequence
    (make empty-element
      gi: "P" attributes: '(("align" "justify")))
    (with-mode footnote-ref
      (process-children))
    (with-mode footnote-def
      (process-matching-children "FOOTNOTE"))))

(element BLOCKQUOTE
  (make element gi: "BLOCKQUOTE"))

(element SCREEN
  (let ((gubbins (make element
                   gi: "PRE"
                   (process-children))))
    (make sequence
      (make empty-element
        gi: "P")
      (if %no-shade-screen%
          gubbins
          (make element
            gi: "TABLE"
            attributes: '(("border" "1")
                          ("bgcolor" "#F4F0EC")
                          ("width" "100%"))
            (make element
              gi: "TR"
              (make element
                gi: "TD"
                gubbins)))))))

(element PHRASE
  (process-children))

(mode footnote-ref
  (element FOOTNOTE
    (make sequence
      (literal "[")
      (literal (format-number (element-number (current-node)) "1"))
      (literal "]"))))

(mode footnote-def
  (element FOOTNOTE
    (make element
      gi: "BLOCKQUOTE"
      (make sequence
        (literal "[")
        (literal (format-number (element-number (current-node)) "1"))
        (literal "]")
        (process-children)))))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; lists

(element ITEMIZEDLIST
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "UL")))

(element ORDEREDLIST
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "OL")))

(element (ITEMIZEDLIST LISTITEM)
  (make sequence
    (make empty-element
      gi: "LI")
    (process-children)
    (make empty-element
      gi: "P")))

(element (ORDEREDLIST LISTITEM)
  (make sequence
    (make empty-element
      gi: "LI")
    (process-children)
    (make empty-element
      gi: "P")))

(element VARIABLELIST
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "DL")))

(element VARLISTENTRY
  (make sequence
    (make empty-element
      gi: "DT")
    (process-children)))

(element (VARLISTENTRY LISTITEM)
  (make sequence
    (make empty-element
      gi: "DD")
    (process-children)
    (make empty-element
      gi: "P")))

(element TERM
  (process-children))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; index

(define (index-entry-name indexterm)
  (string-append "index." (format-number (element-number indexterm) "1")))

(element INDEXTERM
  (make sequence
    (make element
      gi: "A"
      attributes: (list (list "name" (index-entry-name (current-node))))
      (literal ""))
    (empty-sosofo)))

; DIY string>?

(define (string>? s1 s2)
  (let ((len1 (string-length s1))
        (len2 (string-length s2)))
    (let loop ((i 0))
      (cond ((= i len1) #f)
            ((= i len2) #t)
            (#t (let ((c1 (index-char-val (string-ref s1 i)))
                      (c2 (index-char-val (string-ref s2 i))))
                  (cond
                   ((= c1 c2) (loop (+ i 1)))
                   (#t (> c1 c2)))))))))

(define (index-char-val ch)
  (case ch
    ((#\A #\a) 65)
    ((#\B #\b) 66)
    ((#\C #\c) 67)
    ((#\D #\d) 68)
    ((#\E #\e) 69)
    ((#\F #\f) 70)
    ((#\G #\g) 71)
    ((#\H #\h) 72)
    ((#\I #\i) 73)
    ((#\J #\j) 74)
    ((#\K #\k) 75)
    ((#\L #\l) 76)
    ((#\M #\m) 77)
    ((#\N #\n) 78)
    ((#\O #\o) 79)
    ((#\P #\p) 80)
    ((#\Q #\q) 81)
    ((#\R #\r) 82)
    ((#\S #\s) 83)
    ((#\T #\t) 84)
    ((#\U #\u) 85)
    ((#\V #\v) 86)
    ((#\W #\w) 87)
    ((#\X #\x) 88)
    ((#\Y #\y) 89)
    ((#\Z #\z) 90)

    ((#\ ) 32)

    ((#\0) 48)
    ((#\1) 49)
    ((#\2) 50)
    ((#\3) 51)
    ((#\4) 52)
    ((#\5) 53)
    ((#\6) 54)
    ((#\7) 55)
    ((#\8) 56)
    ((#\9) 57)

    ; laziness precludes me from filling this out further
    (else 0)))

; return the string data for a given index entry

(define (get-index-entry-data entry)
  (let ((primary (select-elements (descendants entry) "PRIMARY"))
        (secondary (select-elements (descendants entry) "SECONDARY")))
    (if (node-list-empty? secondary)
        (data primary)
        (string-append (data primary) " - " (data secondary)))))

; insert a pair of the form (index-string . index-markup) into the
; tree of index entries -- each tree node is a three element list (L PAIR R)

(define (insert-index-entry tree entry)
  (let* ((text (get-index-entry-data entry))
         (pear (cons text
                     (make sequence
                       (make empty-element
                         gi: "LI")
                       (make element
                         gi: "A"
                         attributes: (list (list "href"
                                                 (string-append (link-file-name
                                                                 entry)
                                                                "#"
                                                                (index-entry-name
                                                                 entry))))
                         (literal text))))))
    (insert-index-entry-helper pear tree)))

(define (insert-index-entry-helper pear tree)
  (cond ((null? tree)
         (list (list)
               pear
               (list)))
        ((string>? (car pear) (car (car (cdr tree))))
         (list (car tree)
               (car (cdr tree))
               (insert-index-entry-helper pear (car (cdr (cdr tree))))))
        (#t (list (insert-index-entry-helper pear (car tree))
                  (car (cdr tree))
                  (car (cdr (cdr tree)))))))

; build the sorted list and then filter out the markup

(define (make-index)
  (letrec ((traverse (lambda (node)
                       (cond ((null? node) (empty-sosofo))
                             (#t (sosofo-append
                                  (traverse (car node))
                                  (cdr (car (cdr node)))
                                  (traverse (car (cdr (cdr node))))))))))

    (make sequence
      (make element
        gi: "A"
        attributes: (list (list "name" "INDEXTOP"))
        (literal ""))
      (make element
        gi: "H1"
        (literal "Index"))
      (make element
        gi: "UL"
        (traverse (node-list-reduce (select-elements (subtree (current-node))
                                                     "INDEXTERM")
                                    insert-index-entry
                                    '()))))))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; links & cross-references

(define (link-file-name target)
  (cond (%no-split-output% "")
        (#t
         (string-append
          %output-basename%
          "-"
          (cond ((equal? (gi target) "CHAPTER")
                 (format-number (child-number target) "1"))
                ((ancestor-child-number "CHAPTER" target)
                 (format-number (ancestor-child-number "CHAPTER" target) "1"))
                ((equal? (gi target) "APPENDIX")
                 (format-number (child-number target) "A"))
                ((ancestor-child-number "APPENDIX" target)
                 (format-number (ancestor-child-number "APPENDIX" target) "A"))
                (#t ""))
          %output-suffix%))))

(element LINK
  (let* ((target (element-with-id (attribute-string "linkend")
                                  (ancestor "BOOK")))
         (target-file-name (link-file-name target)))
    (make element
      gi: "A"
      attributes: (list
                   (list "href"
                         (string-append
                          target-file-name
                          "#"
                          (attribute-string "linkend")))))))
(element ULINK
  (make element
    gi: "A"
    attributes: (list
                 (list "href" (attribute-string "url")))))

(element XREF
  (let* ((target (element-with-id (attribute-string "linkend")
                                  (ancestor "BOOK")))
         (target-file-name (link-file-name target)))
    (make element
      gi: "A"
      attributes: (list
                   (list "href"
                         (string-append target-file-name
                                        "#"
                                        (attribute-string "linkend"))))
      (with-mode extract-xref-text
        (process-node-list target)))))

(mode extract-xref-text
  (default
    (let ((title-sosofo (with-mode extract-title-text
                          (process-first-descendant "TITLE"))))
      (if (sosofo? title-sosofo)
          title-sosofo
          (literal (string-append "Reference to " (gi)))))))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; figures

(element FIGURE
  (make sequence
    (make empty-element
      gi: "P")
    (make-anchor)
    (process-children)
    (make empty-element
      gi: "P")))

(element (FIGURE TITLE)
  (make sequence
    (make element
      gi: "B")
    (make empty-element
      gi: "P")))

(element GRAPHIC
  (let ((img
         (make sequence
           (make empty-element
             gi: "P")
           (make empty-element
             gi: "IMG"
             attributes: (list
                          (list "src" (attribute-string "fileref")))))))
    (if (equal?
         (attribute-string "align")
         "CENTER")
        (make element
          gi: "CENTER"
          img)
        img)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; tables

(element INFORMALTABLE
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "TABLE"
      attributes: (if (equal?
                       (attribute-string "frame")
                       "ALL")
                      '(("border" "2") ("cellpadding" "2"))
                      '()))
    (make empty-element
      gi: "P")))

(element TGROUP
  (process-children))

(element THEAD
  (process-children))

(element (THEAD ROW)
  (make sequence
    (make empty-element
      gi: "TR")
    (process-children)))

(element (THEAD ROW ENTRY)
  (make sequence
    (make empty-element
      gi: "TD")
    (make element
      gi: "B"
      (process-children))))

(element TBODY
  (process-children))

(element (TBODY ROW)
  (make sequence
    (make empty-element
      gi: "TR")
    (process-children)))

(element (TBODY ROW ENTRY)
  (make sequence
    (make empty-element
      gi: "TD")
    (process-children)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; book info

(element BOOKINFO
  (make sequence
    (make element
      gi: "CENTER"
      (process-children))
    (cond ((not %no-make-toc%)
           (make sequence
             ;;(make-fat-rule)
             (make element
               gi: "H2"
               (literal "Zawarto¶æ"))
             (make element
               gi: "ul"
               (with-mode make-toc-links
                 (process-node-list (ancestor "BOOK"))))))
          (#t (empty-sosofo)))))

(element BOOKBIBLIO
  (process-children))

(element AUTHORGROUP
  (make sequence
    (make empty-element
      gi: "P")
    (process-children)))

(element AUTHOR
  (make sequence
    (process-children)
    (make empty-element
      gi: "br")))

(element FIRSTNAME
  (make element
    gi: "B"))

(element OTHERNAME
  (make element
    gi: "I"))

(element SURNAME
  (make element
    gi: "B"))

(element TITLE
  (make element
    gi: "H1"))

(element LEGALNOTICE
  (make sequence
    (make empty-element
      gi: "P")
    (process-children)))

(element YEAR
  (make element
    gi: "B"))

(element HOLDER
  (make element
    gi: "B"))
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; TOC

(element LOF
  (empty-sosofo))

(element LOT
  (empty-sosofo))

(element TOC
  (empty-sosofo))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; DIY TOC

(mode make-toc-links
  (element (BOOK)
    (sosofo-append
     (process-children)
     (cond ((not %no-make-index%)
            (make sequence
              (make empty-element
                gi: "LI")
              (make element
                gi: "A"
                attributes: (list (list "href"
                                        (string-append %output-basename%
                                                       "-INDEX"
                                                       %output-suffix%
                                                       "#INDEXTOP")))
                (literal "Index"))))
           (#t (empty-sosofo)))))
  (element (CHAPTER)
    (make-chap-or-app-toc-links))
  (element (APPENDIX)
    (make-chap-or-app-toc-links))
  (element (SECT1)
    (make sequence
      (make empty-element
        gi: "LI")
      (let ((title-text (with-mode extract-title-text
                          (process-first-descendant "TITLE"))))
        (if (id)
            (make element
              gi: "A"
              attributes: (list (list "href" (string-append (link-file-name (current-node))
                                                            "#"
                                                            (id))))
            title-text)
            title-text))))
  (default
    (empty-sosofo)))

(define (make-chap-or-app-toc-links)
  (make sequence
    (make empty-element
      gi: "LI")
    (let ((title-text
           (make sequence
             (literal (if (equal? (gi) "CHAPTER")
                          (string-append "Rozdzia³ "
                                         (format-number
                                          (element-number (current-node))
                                          "1")
                                         " - ")
                          (string-append "Dodatek "
                                         (format-number
                                          (element-number (current-node))
                                          "A")
                                         " - ")))
             (with-mode extract-title-text
               (process-first-descendant "TITLE")))))
      (if (id)
          (make element
            gi: "A"
            attributes: (list (list "href" (string-append (link-file-name (current-node))
                                                                          "#"
                                                                          (id))))
            title-text)
          title-text))
    (make element
      gi: "UL"
      (with-mode make-toc-links
        (process-matching-children "SECT1")))))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; local additions

(element HRULE
  (make empty-element gi: "HR"))

(element BOLD
  (make element gi: "B"))

;; Jakie¶ takie dziwne i inne

(element PROMPT
  (make element gi: "B"))

(element USERINPUT
  (process-children))
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; make the unimplemented bits stand out

(default
  (make element gi: "FONT" attributes: '(("color" "brown"))))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; standard subroutines

(define (node-list-reduce nl combine init)
  (if (node-list-empty? nl)
      init
      (node-list-reduce (node-list-rest nl)
                        combine
                        (combine init (node-list-first nl)))))

(define (subtree nl)
  (node-list-map (lambda (snl)
                   (node-list snl (subtree (children snl))))
                 nl))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; various homebrew subroutines

(define (make-fat-rule)
  (make empty-element
    gi: "HR"
    attributes: '(("size" "5"))))

(define (chap-app-head-label chap-or-app)
  (let ((label
         (attribute-string "label" (ancestor chap-or-app))))
    (string-append
     chap-or-app
     " "
     (if label
         (if (equal? label "auto")
             (format-number
              (element-number (ancestor chap-or-app))
              (if (equal? chap-or-app "Chapter") "1" "A"))
           label)
       (format-number
        (element-number (ancestor chap-or-app))
        (if (equal? chap-or-app "Chapter") "1" "A")))
     ". ")))

(define (make-anchor)
  (if (id)
      (make element
        gi: "A"
        attributes: (list (list "name" (id)))
        (literal ""))
      (empty-sosofo)))

(define (make-special-para label)
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "B"
      (literal label))
    (make element
      gi: "BLOCKQUOTE"
      (process-children))))

(define (make-warning-para label)
  (make sequence
    (make empty-element
      gi: "P")
    (make element
      gi: "FONT" attributes: '(("color" "red"))
    (make element
      gi: "B"
      (literal label)))
    (make element
      gi: "BLOCKQUOTE"
      (process-children))))
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;; the end
