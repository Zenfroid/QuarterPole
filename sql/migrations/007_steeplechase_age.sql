-- Steeplechases are for 4-year-olds and up. Fixes races already on the cards
-- that were made for 3-year-olds and up.
UPDATE qp_races SET age_min = 4 WHERE surface = 'steeplechase' AND age_min < 4 AND status = 'open';
UPDATE qp_stakes SET age_min = 4 WHERE surface = 'steeplechase' AND age_min < 4;
