CREATE TABLE family_member_spouses (
    id INT NOT NULL AUTO_INCREMENT,
    family_member_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_family_member_spouses_member_id (family_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
