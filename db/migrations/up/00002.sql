-- @description: Create NaiveBayes tables
CREATE TABLE nb_internals (
    category VARCHAR(255) NOT NULL,
    doc_count INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (category)
);

CREATE TABLE nb_wordlist (
    token VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    count INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (token, category)
);
