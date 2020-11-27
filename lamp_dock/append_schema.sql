CREATE TABLE ec_item_details (
    item_id INT,
    brand VARCHAR(100),
    maker VARCHAR(100),
    country VARCHAR(100),
    material VARCHAR(100),
    width INT,
    depth INT,
    height INT,
    weight INT,
    create_datetime datetime,
    update_datetime datetime,
    primary key(item_id)
);

CREATE TABLE ec_recipe_details (
    recipe_id INT,
    person INT,
    recipe_material VARCHAR(100),
    recipe VARCHAR(100),
    point VARCHAR(100),
    create_datetime datetime,
    update_datetime datetime,
    primary key(recipe_id)
); 