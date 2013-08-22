CREATE TABLE IF NOT EXISTS `signatures` (
    `apikey` TEXT,
    `signature` TEXT,
    `timestamp` INTEGER,
    UNIQUE (`apikey`, `signature`, `timestamp`) ON CONFLICT FAIL
);
