-- For databases created before the Jockeys admin page.
-- Retired jockeys (active = 0) get no new rides but keep their name in old race charts.
ALTER TABLE qp_jockeys ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1;

-- 18 more jockeys (30 in all), so a full field of 12 always has 12 different riders.
INSERT INTO qp_jockeys (name, skill) VALUES
('M. Fitzgerald', 50), ('N. Haddad', 50), ('O. Petrova', 50), ('P. Gallagher', 50),
('R. Ortega', 50), ('S. Mbeki', 50), ('T. Halloran', 50), ('V. Rossi', 50),
('W. Achterberg', 50), ('Y. Tanaka', 50), ('Z. Novak', 50), ('A. Beaumont', 50),
('C. MacLeod', 50), ('D. Quintero', 50), ('E. Varga', 50), ('G. Oyelaran', 50),
('J. Thibodeaux', 50), ('L. Harrington', 50);
