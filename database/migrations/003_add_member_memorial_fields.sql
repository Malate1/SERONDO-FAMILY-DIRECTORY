ALTER TABLE family_members
    ADD COLUMN is_deceased TINYINT(1) NOT NULL DEFAULT 0 AFTER birth_date,
    ADD COLUMN death_date DATE NULL AFTER is_deceased,
    ADD COLUMN tribute TEXT NULL AFTER death_date,
    ADD KEY idx_family_members_memorial (is_deceased, death_date);
