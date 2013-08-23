CREATE TABLE IF NOT EXISTS signatures (
    apikey TEXT,
    signature TEXT,
    expires INTEGER,
    UNIQUE (apikey, signature) ON CONFLICT FAIL
);
