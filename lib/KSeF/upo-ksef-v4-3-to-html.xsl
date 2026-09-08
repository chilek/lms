<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:upo="http://upo.schematy.mf.gov.pl/KSeF/v4-3"
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  exclude-result-prefixes="upo ds">

  <xsl:output method="html" encoding="UTF-8" indent="no"/>
  <xsl:strip-space elements="*"/>

  <!-- Helper: drukuj wiersz tylko jeśli value niepuste -->
  <xsl:template name="row-if">
    <xsl:param name="label"/>
    <xsl:param name="value"/>
    <xsl:if test="string-length(normalize-space($value)) &gt; 0">
      <tr>
        <th><xsl:value-of select="$label"/></th>
        <td><xsl:value-of select="normalize-space($value)"/></td>
      </tr>
    </xsl:if>
  </xsl:template>

  <!-- Helper: skróć długie wartości (np. base64) -->
  <xsl:template name="shorten">
    <xsl:param name="s"/>
    <xsl:param name="head" select="24"/>
    <xsl:param name="tail" select="24"/>
    <xsl:variable name="t" select="normalize-space($s)"/>
    <xsl:choose>
      <xsl:when test="string-length($t) &gt; ($head + $tail + 3)">
        <span class="mono">
          <xsl:value-of select="substring($t, 1, $head)"/>
          <xsl:text>…</xsl:text>
          <xsl:value-of select="substring($t, string-length($t) - $tail + 1, $tail)"/>
        </span>
      </xsl:when>
      <xsl:otherwise>
        <span class="mono"><xsl:value-of select="$t"/></span>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="/">
    <html>
      <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>UPO KSeF – wizualizacja</title>
        <style>
          :root{
            --bg:#0b0f14;
            --card:#111826;
            --muted:#94a3b8;
            --text:#e5e7eb;
            --ok:#10b981;
            --warn:#f59e0b;
            --bad:#ef4444;
            --border:rgba(148,163,184,.18);
            --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            --sans: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
          }
          body{margin:0; font-family:var(--sans); background:var(--bg); color:var(--text); line-height:1.35;}
          .wrap{max-width:1500px; margin:24px auto; padding:0 16px;}
          .header{display:flex; gap:12px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;}
          h1{margin:0; font-size:22px;}
          .subtitle{color:var(--muted); font-size:13px; margin-top:6px;}
          .pill{display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border:1px solid var(--border); border-radius:999px; font-size:12px; color:var(--muted);}
          .grid{display:grid; grid-template-columns: 1fr; gap:14px; margin-top:14px;}
          @media (min-width: 920px){ .grid{grid-template-columns: 1.10fr .90fr;} }
          .card{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px;}
          .card h2{margin:0 0 10px; font-size:14px; color:#cbd5e1; letter-spacing:.2px; text-transform:uppercase;}
          table{width:100%; border-collapse:collapse;}
          th, td{padding:8px 10px; border-top:1px solid var(--border); vertical-align:top;}
          th{width:38%; color:#cbd5e1; font-weight:600; text-align:left;}
          td{color:var(--text); word-break:break-word;}
          .mono{font-family:var(--mono);}
          .status{
            display:flex; align-items:center; gap:10px; padding:10px 12px;
            border:1px solid var(--border); border-radius:12px; background:rgba(148,163,184,.06);
            margin-bottom:10px;
          }
          .badge{font-size:12px; padding:3px 8px; border-radius:999px; border:1px solid var(--border);}
          .ok{color:var(--ok);}
          .warn{color:var(--warn);}
          .bad{color:var(--bad);}
          .small{color:var(--muted); font-size:12px;}
          hr{border:0;border-top:1px solid var(--border); margin:14px 0;}
        </style>
      </head>

      <body>
        <div class="wrap">
          <xsl:variable name="p" select="/upo:Potwierdzenie"/>

          <xsl:variable name="ksefNumber" select="$p/upo:Dokument/upo:NumerKSeFDokumentu"/>
          <xsl:variable name="isAccepted" select="string-length(normalize-space($ksefNumber)) &gt; 0"/>

          <div class="header">
            <div>
              <h1>UPO KSeF – wizualizacja</h1>
              <div class="subtitle">
                Namespace: <span class="mono">http://upo.schematy.mf.gov.pl/KSeF/v4-3</span>
                <xsl:text> • </xsl:text>
                Root: <span class="mono">Potwierdzenie</span>
              </div>
            </div>
            <div class="pill">
              Arkusz: <span class="mono">upo-ksef-v4-3-to-html.xsl</span>
            </div>
          </div>

          <div class="grid">
            <!-- LEWA KOLUMNA -->
            <div class="card">
              <h2>Podsumowanie</h2>

              <div class="status">
                <span class="badge">
                  <xsl:attribute name="class">
                    <xsl:text>badge </xsl:text>
                    <xsl:choose>
                      <xsl:when test="$isAccepted">ok</xsl:when>
                      <xsl:otherwise>bad</xsl:otherwise>
                    </xsl:choose>
                  </xsl:attribute>
                  <xsl:choose>
                    <xsl:when test="$isAccepted">Przyjęto</xsl:when>
                    <xsl:otherwise>Nieprzyjęto / brak numeru KSeF</xsl:otherwise>
                  </xsl:choose>
                </span>
                <div class="small">
                  <xsl:text>Numer KSeF jest traktowany jako potwierdzenie przyjęcia (w tym pliku UPO brak osobnego pola statusu).</xsl:text>
                </div>
              </div>

              <table>
                <tbody>
                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Podmiot przyjmujący'"/>
                    <xsl:with-param name="value" select="$p/upo:NazwaPodmiotuPrzyjmujacego"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Numer referencyjny sesji'"/>
                    <xsl:with-param name="value" select="$p/upo:NumerReferencyjnySesji"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Struktura logiczna (XSD)'"/>
                    <xsl:with-param name="value" select="$p/upo:NazwaStrukturyLogicznej"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Kod formularza'"/>
                    <xsl:with-param name="value" select="$p/upo:KodFormularza"/>
                  </xsl:call-template>
                </tbody>
              </table>

              <hr/>

              <h2>Uwierzytelnienie</h2>
              <table>
                <tbody>
                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Kontekst (NIP)'"/>
                    <xsl:with-param name="value" select="$p/upo:Uwierzytelnienie/upo:IdKontekstu/upo:Nip"/>
                  </xsl:call-template>

                  <tr>
                    <th>Skrót dokumentu uwierzytelniającego</th>
                    <td>
                      <xsl:call-template name="shorten">
                        <xsl:with-param name="s" select="$p/upo:Uwierzytelnienie/upo:SkrotDokumentuUwierzytelniajacego"/>
                        <xsl:with-param name="head" select="28"/>
                        <xsl:with-param name="tail" select="16"/>
                      </xsl:call-template>
                    </td>
                  </tr>
                </tbody>
              </table>

              <hr/>

              <h2>Dokument</h2>
              <table>
                <tbody>
                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'NIP sprzedawcy'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:NipSprzedawcy"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Numer KSeF dokumentu'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:NumerKSeFDokumentu"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Numer faktury'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:NumerFaktury"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Data wystawienia faktury'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:DataWystawieniaFaktury"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Data przesłania dokumentu'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:DataPrzeslaniaDokumentu"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Data nadania numeru KSeF'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:DataNadaniaNumeruKSeF"/>
                  </xsl:call-template>

                  <tr>
                    <th>Skrót dokumentu (hash)</th>
                    <td>
                      <xsl:call-template name="shorten">
                        <xsl:with-param name="s" select="$p/upo:Dokument/upo:SkrotDokumentu"/>
                        <xsl:with-param name="head" select="28"/>
                        <xsl:with-param name="tail" select="16"/>
                      </xsl:call-template>
                    </td>
                  </tr>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'Tryb wysyłki'"/>
                    <xsl:with-param name="value" select="$p/upo:Dokument/upo:TrybWysylki"/>
                  </xsl:call-template>
                </tbody>
              </table>
            </div>

            <!-- PRAWA KOLUMNA -->
            <div class="card">
              <h2>Podpis (XMLDSIG)</h2>

              <table>
                <tbody>
                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'CanonicalizationMethod'"/>
                    <xsl:with-param name="value" select="$p/ds:Signature/ds:SignedInfo/ds:CanonicalizationMethod/@Algorithm"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'SignatureMethod'"/>
                    <xsl:with-param name="value" select="$p/ds:Signature/ds:SignedInfo/ds:SignatureMethod/@Algorithm"/>
                  </xsl:call-template>

                  <xsl:call-template name="row-if">
                    <xsl:with-param name="label" select="'DigestMethod'"/>
                    <xsl:with-param name="value" select="$p/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestMethod/@Algorithm"/>
                  </xsl:call-template>

                  <tr>
                    <th>DigestValue</th>
                    <td>
                      <xsl:call-template name="shorten">
                        <xsl:with-param name="s" select="$p/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue"/>
                        <xsl:with-param name="head" select="24"/>
                        <xsl:with-param name="tail" select="24"/>
                      </xsl:call-template>
                    </td>
                  </tr>

                  <tr>
                    <th>SignatureValue</th>
                    <td>
                      <xsl:call-template name="shorten">
                        <xsl:with-param name="s" select="$p/ds:Signature/ds:SignatureValue"/>
                        <xsl:with-param name="head" select="24"/>
                        <xsl:with-param name="tail" select="24"/>
                      </xsl:call-template>
                    </td>
                  </tr>

                  <tr>
                    <th>X509Certificate</th>
                    <td>
                      <xsl:call-template name="shorten">
                        <xsl:with-param name="s" select="$p/ds:Signature/ds:KeyInfo/ds:X509Data/ds:X509Certificate"/>
                        <xsl:with-param name="head" select="28"/>
                        <xsl:with-param name="tail" select="28"/>
                      </xsl:call-template>
                      <div class="small" style="margin-top:6px;">
                        (w pliku jest sam certyfikat; brak X509SubjectName / IssuerSerial)
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <xsl:comment>
              <hr/>

              <h2>Surowe dane (opcjonalnie)</h2>
              <div class="small">
                Jeżeli chcesz, mogę dopisać w XSLT przełącznik (parametr), który pozwala dołączać/ukrywać pełny XML w &lt;pre&gt;.
              </div>

              </xsl:comment>
            </div>
          </div>
        </div>
      </body>
    </html>
  </xsl:template>

</xsl:stylesheet>
