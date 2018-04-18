INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSNovitusHdPlugin', '2018032700');

DROP TABLE IF EXISTS novitus_fiscalized_invoices;
CREATE TABLE novitus_fiscalized_invoices (
    doc_id integer          NOT NULL,
    fiscalized smallint     DEFAULT 1 NOT NULL,
        FOREIGN KEY (doc_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
    );

INSERT INTO uiconfig (section, var, value, description) VALUES
('novitus','ip_address','','Fiskal Printer IP address'),
('novitus','port','6001','Fiskal Printer port');


