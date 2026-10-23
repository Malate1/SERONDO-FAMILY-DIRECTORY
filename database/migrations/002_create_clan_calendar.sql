CREATE TABLE clan_events (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    category VARCHAR(24) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    clan_id INT NULL,
    created_by_rep_id INT NOT NULL,
    created_by_clan_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clan_events_starts_at (starts_at),
    KEY idx_clan_events_clan_id (clan_id),
    KEY idx_clan_events_created_by_clan_id (created_by_clan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE clan_event_rsvps (
    id INT NOT NULL AUTO_INCREMENT,
    event_id INT NOT NULL,
    clan_id INT NOT NULL,
    headcount INT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clan_event_rsvps_event_clan (event_id, clan_id),
    KEY idx_clan_event_rsvps_clan_id (clan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
