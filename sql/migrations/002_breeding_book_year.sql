-- For databases created before breeding: book sizes count the game year a breeding was booked.
ALTER TABLE qp_breedings
  ADD COLUMN bred_in_game_year SMALLINT NOT NULL AFTER foal_game_year,
  DROP INDEX ix_breed_sire_year,
  ADD KEY ix_breed_sire_year (sire_id, bred_in_game_year);
