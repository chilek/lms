<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:etd="http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/"
	xmlns:dir="http://jpk.mf.gov.pl/wzor/2017/11/13/1113/"
    exclude-result-prefixes="xs" version="1.0">
  <xsl:output method="text" indent="no"/>
  <xsl:template match="/">
    <xsl:apply-templates select="dir:JPK" mode="header"/>
    <xsl:apply-templates select="dir:JPK" mode="data"/>
  </xsl:template>

  <!-- HEADER ROW -->
  <xsl:template match="dir:JPK" mode="header">
    <xsl:text>KodFormularza</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>kodSystemowy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>wersjaSchemy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>WariantFormularza</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>CelZlozenia</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataWytworzeniaJPK</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataOd</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataDo</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NazwaSystemu</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NIP</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>PelnaNazwa</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>Email</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>LpSprzedazy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NrKontrahenta</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NazwaKontrahenta</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>AdresKontrahenta</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DowodSprzedazy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataWystawienia</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataSprzedazy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_10</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_11</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_12</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_13</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_14</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_15</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_16</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_17</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_18</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_19</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_20</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_21</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_22</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_23</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_24</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_25</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_26</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_27</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_28</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_29</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_30</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_31</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_32</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_33</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_34</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_35</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_36</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_37</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_38</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_39</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>LiczbaWierszySprzedazy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>PodatekNalezny</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>LpZakupu</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NrDostawcy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>NazwaDostawcy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>AdresDostawcy</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DowodZakupu</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataZakupu</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>DataWplywu</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_43</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_44</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_45</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_46</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_47</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_48</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_49</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>K_50</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>LiczbaWierszyZakupow</xsl:text>
    <xsl:text>;</xsl:text>
    <xsl:text>PodatekNaliczony</xsl:text>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:JPK" mode="data">
    <xsl:apply-templates select="dir:Naglowek"/>
    <xsl:apply-templates select="dir:Podmiot1"/>
    <xsl:apply-templates select="dir:SprzedazWiersz"/>
    <xsl:apply-templates select="dir:SprzedazCtrl"/>
    <xsl:apply-templates select="dir:ZakupWiersz"/>
    <xsl:apply-templates select="dir:ZakupCtrl"/>
  </xsl:template>

  <xsl:template match="dir:Naglowek">
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:KodFormularza"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:KodFormularza/@kodSystemowy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:KodFormularza/@wersjaSchemy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:WariantFormularza"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:CelZlozenia"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataWytworzeniaJPK"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataOd"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataDo"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NazwaSystemu"/>
    </xsl:call-template>
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:Podmiot1">
    <xsl:value-of select="';;;;;;;;;'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NIP"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:PelnaNazwa"/>
    </xsl:call-template>
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:text>&#10;</xsl:text>
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:SprzedazWiersz">
    <xsl:value-of select="';;;;;;;;;;;;'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:LpSprzedazy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NrKontrahenta"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NazwaKontrahenta"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:AdresKontrahenta"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DowodSprzedazy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataWystawienia"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataSprzedazy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_10"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_11"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_12"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_13"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_14"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_15"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_16"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_17"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_18"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_19"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_20"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_21"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_22"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_23"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_24"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_25"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_26"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_27"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_28"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_29"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_30"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_31"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_32"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_33"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_34"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_35"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_36"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_37"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_38"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_39"/>
    </xsl:call-template>
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;'"/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:SprzedazCtrl">
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:LiczbaWierszySprzedazy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:PodatekNalezny"/>
    </xsl:call-template>
    <xsl:value-of select="';;;;;;;;;;;;;;;;;'"/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:ZakupWiersz">
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:LpZakupu"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NrDostawcy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:NazwaDostawcy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:AdresDostawcy"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DowodZakupu"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataZakupu"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:DataWplywu"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_43"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_44"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_45"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_46"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_47"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_48"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_49"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:K_50"/>
    </xsl:call-template>
    <xsl:value-of select="';;'"/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template match="dir:ZakupCtrl">
    <xsl:value-of select="';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;'"/>
    <xsl:call-template name="singular_field">
      <xsl:with-param name="fieldname" select="dir:LiczbaWierszyZakupow"/>
    </xsl:call-template>
    <xsl:value-of select="';'"/>
    <xsl:call-template name="numeric_field">
      <xsl:with-param name="fieldname" select="dir:PodatekNaliczony"/>
    </xsl:call-template>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <xsl:template name="singular_field">
    <xsl:param name="fieldname"/>
    <xsl:variable name="linefeed">
      <xsl:text>&#10;</xsl:text>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="contains( $fieldname, '&quot;' )">
        <!-- Field contains a quote. We must enclose this field in quotes,
             and we must escape each of the quotes in the field value.
        -->
        <xsl:text>"</xsl:text>
        <xsl:call-template name="escape_quotes">
          <xsl:with-param name="string" select="$fieldname"/>
        </xsl:call-template>
        <xsl:text>"</xsl:text>
      </xsl:when>
      <xsl:when test="contains( $fieldname, ';' ) or contains( $fieldname, $linefeed )">
        <!-- Field contains a comma and/or a linefeed.
             We must enclose this field in quotes.
        -->
        <xsl:text>"</xsl:text>
        <xsl:value-of select="$fieldname"/>
        <xsl:text>"</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <!-- No need to enclose this field in quotes.
        -->
        <xsl:value-of select="$fieldname"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="numeric_field">
    <xsl:param name="fieldname"/>
    <xsl:choose>
      <xsl:when test="not($fieldname)">
        <xsl:text>0</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="translate( $fieldname, '.', ',' )"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="escape_quotes">
    <xsl:param name="string"/>
    <xsl:value-of select="substring-before( $string, '&quot;' )"/>
    <xsl:text>""</xsl:text>
    <xsl:variable name="substring_after_first_quote" select="substring-after( $string, '&quot;' )"/>
    <xsl:choose>
      <xsl:when test="not( contains( $substring_after_first_quote, '&quot;' ) )">
        <xsl:value-of select="$substring_after_first_quote"/>
      </xsl:when>
      <xsl:otherwise>
        <!-- The substring after the first quote contains a quote.
             So, we call ourself recursively to escape the quotes
             in the substring after the first quote.
        -->
        <xsl:call-template name="escape_quotes">
          <xsl:with-param name="string" select="$substring_after_first_quote"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
