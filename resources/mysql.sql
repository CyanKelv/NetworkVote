-- #! mysql
-- #{ network
-- #    { init_votes
CREATE TABLE IF NOT EXISTS votes(
    name VARCHAR(128) PRIMARY KEY,
    servers TEXT,
    lastupdated INTEGER
);
-- #    }

-- #    { get_vote
-- #      :name string
SELECT name, servers, lastupdated FROM votes WHERE name = :name;
-- #    }

-- #    { get_vote_by_date
-- #      :timestamp integer
SELECT name, servers, lastupdated FROM votes WHERE lastupdated < :timestamp;
-- #    }

-- #    { update_vote
-- #      :name string
-- #      :servers string
-- #      :timestamp integer
INSERT OR REPLACE INTO votes(name, servers, lastupdated) VALUES (:name, :servers, :timestamp);
-- #    }

-- #    { delete_vote
-- #      :name string
DELETE FROM votes where name = :name;
-- #    }
-- #}