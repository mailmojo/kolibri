DROP TABLE items;

CREATE TABLE items (
    name TEXT PRIMARY KEY NOT NULL,
    description TEXT DEFAULT NULL,
    price NUMERIC DEFAULT NULL,
    added DATE NOT NULL,
    received DATE DEFAULT NULL
);

INSERT INTO items (name, description, price, added, received)
VALUES('Blue Bike', 'My favorite bike in the shop', 299, '2009-04-20', '2009-04-21');

INSERT INTO items (name, description, price, added, received)
VALUES('Toy house', 'Garden toy house with several floors', 1199, '2009-04-14', '2009-04-20');