CREATE TABLE IF NOT EXISTS urls (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255),
    created_at timestamp);

CREATE TABLE IF NOT EXISTS urls_checks (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    url_id bigint REFERENCES urls (id),
    status_code int,
    h1 varchar(255),
    title text,
    description text,
    created_at timestamp);

