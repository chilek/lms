<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html>
  <body>
  <h2>MaxCon - XML synch</h2>
  <table border="1">
    <tr bgcolor="#9acd32">
      <th>Manufacturer<br />
      (name)</th>
      <th>Manufacturer<br />(id)</th>
      <th>Product name</th>
      <th>Product id</th>
      <th>Quantity in unit</th>
      <th>Group name</th>
      <th>Group id</th>
      <th>Stock</th>
      <th>Price (net)</th>
      <th>SRP (gross)</th>
      <th>Last modificatiton date<br />(timestamp)</th>
    </tr>
    <xsl:for-each select="stockInfo/product">
    <tr>
      <td><xsl:value-of select="mname"/></td>
      <td><xsl:value-of select="mid"/></td>
      <td><xsl:value-of select="pname"/></td>
      <td><xsl:value-of select="id"/></td>
      <td><xsl:value-of select="quantity"/></td>
      <td><xsl:value-of select="gname"/></td>
      <td><xsl:value-of select="gid"/></td>
      <td><xsl:value-of select="count"/></td>
      <td><xsl:value-of select="gprice"/></td>
      <td><xsl:value-of select="srp"/></td>
      <td><xsl:value-of select="moddate"/></td>
    </tr>
    </xsl:for-each>
  </table>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet> 
