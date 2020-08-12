<?php
    require_once './banco.php';
    $banco = new banco();

    if($_GET['type'] == 'attributeproduct') {
        echo 'TRUNCATE TABLE `oc_product_option`;<br>';
        echo 'TRUNCATE TABLE `oc_product_option_value`;<br>';
        $sql = "SELECT
                pac.id_attribute,
                pa.id_product,
                ag.id_attribute_group
            FROM	
                ps_product_attribute_combination as pac
            INNER JOIN
                ps_product_attribute as pa on pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN
                ps_attribute as a on pac.id_attribute = a.id_attribute
            INNER JOIN 
                ps_attribute_group as ag on a.id_attribute_group = ag.id_attribute_group
            GROUP BY 
                pa.id_product";
        
        foreach($banco->assoc($sql) as $i => $opcao) {
            echo "INSERT INTO oc_product_option (product_id, option_id) VALUES ('".$opcao['id_product']."', '".$opcao['id_attribute_group']."');<br>";
            $sql_i = "SELECT
                    pac.*,
                    pa.*,
                    ag.id_attribute_group
                FROM	
                    ps_product_attribute_combination as pac
                INNER JOIN
                    ps_product_attribute as pa on pac.id_product_attribute = pa.id_product_attribute
                INNER JOIN
                    ps_attribute as a on pac.id_attribute = a.id_attribute
                INNER JOIN 
                    ps_attribute_group as ag on a.id_attribute_group = ag.id_attribute_group
                WHERE 
                    pa.id_product = ".$opcao['id_product']."
                AND
                    ag.id_attribute_group = ".$opcao['id_attribute_group'];
            
            foreach($banco->assoc($sql_i) as $opcaoi) {
                $quantity = $opcaoi['quantity'] > 0 ? $opcaoi['quantity'] : 99; 
                echo "INSERT INTO oc_product_option_value 
                    (
                        product_option_id, 
                        product_id, option_id, 
                        option_value_id, 
                        quantity, 
                        price, 
                        price_prefix,
                        weight
                    ) VALUES (
                        '".($i+1)."', 
                        '".$opcaoi['id_product']."', 
                        '".$opcaoi['id_attribute_group']."', 
                        '".$opcaoi['id_attribute']."', 
                        '".$quantity."', 
                        '".$opcaoi['price']."', 
                        '+',
                        '".$opcaoi['weight']."'
                    );<br>";
            }
        }
    }

    if($_GET['type'] == 'attribute') {
        echo 'TRUNCATE TABLE `oc_option`;<br>';
        echo 'TRUNCATE TABLE `oc_option_value`;<br>';
        echo 'TRUNCATE TABLE `oc_option_description`;<br>';
        echo 'TRUNCATE TABLE `oc_option_value_description`;<br>';
        foreach($banco->assoc("SELECT * FROM `ps_attribute_group`") as $opcao) {
            echo "INSERT INTO oc_option (option_id, type, sort_order) VALUES ('".$opcao["id_attribute_group"]."', '".$opcao["group_type"]."', '".$opcao["position"]."');<br>";
        }

        foreach($banco->assoc("SELECT * FROM `ps_attribute`") as $opcao) {
            echo "INSERT INTO oc_option_value (option_value_id, option_id, sort_order) VALUES ('".$opcao['id_attribute']."', '".$opcao['id_attribute_group']."', '".$opcao['position']."');<br>";
        }

        foreach($banco->assoc("SELECT * FROM `ps_attribute_group_lang` WHERE id_lang = 2") as $opcao) {
            echo "INSERT INTO oc_option_description (option_id, language_id, name) VALUES ('".$opcao['id_attribute_group']."', '1', '".$opcao['name']."');<br>";
        }

        foreach($banco->assoc("SELECT l.*, a.id_attribute_group, a.id_attribute as id FROM `ps_attribute_lang` as l INNER JOIN ps_attribute as a on l.id_attribute = a.id_attribute WHERE l.id_lang = 2") as $opcao) {
            echo "INSERT INTO oc_option_value_description (option_value_id, language_id, option_id, name) VALUES ('".$opcao['id']."', '1', '".$opcao['id_attribute_group']."', '".$opcao['name']."');<br>";
        }
    }

    if($_GET['type'] == 'categoryproduct') {
        echo 'TRUNCATE TABLE ps_category_product;<br>';
        foreach ($banco->assoc("SELECT * FROM `ps_category_product`") as $cp) {
            echo 'INSERT INTO oc_product_to_category (product_id, category_id) VALUES ("'.$cp['id_product'].'", "'.$cp['id_category'].'");<br>';
        }
    }

    if($_GET['type'] == 'image') {
        echo 'TRUNCATE TABLE oc_product_image;<br>';
        foreach ($banco->assoc("SELECT * FROM `ps_image`") as $image) {
            //https://www.rejanedosanjos.com.br/loja/img/p/1/4/1/4/1414.jpg
            $id_image = str_split($image['id_image']);
            $id_image = implode("/", $id_image);
            if ($image['cover'] == 1) {
                echo 'UPDATE oc_product SET image = "p/'.$id_image.'/'.$image['id_image'].'.jpg" WHERE product_id = "'.$image['id_product'].'";<br>';
            } else {
                echo 'INSERT INTO oc_product_image (product_id, image) VALUES ("'.$image['id_product'].'", "p/'.$id_image.'/'.$image['id_image'].'.jpg");<br>';
            }
        }
    }

    if($_GET['type'] == 'product') {
        echo "<pre>";
        echo 'TRUNCATE TABLE oc_product_to_store;<br>';
        echo 'TRUNCATE TABLE oc_product;<br>';
        echo 'TRUNCATE TABLE oc_product_description;<br>';
        foreach($banco->assoc("SELECT * FROM ps_product") as $product) {
            echo 'INSERT INTO
                oc_product_to_store
            (
                product_id,
                store_id
            ) VALUES (
                "'.$product['id_product'].'",
                "0"
            );<br>
            ';
        }
        foreach($banco->assoc("SELECT * FROM ps_product") as $product) {
            echo 'INSERT INTO
                oc_product
            (
                product_id,
                upc,
                ean,
                price,
                weight,
                length,
                width,
                height,
                status,
                date_added,
                date_modified
            ) VALUES (
                "'.$product['id_product'].'",
                "'.$product['upc'].'",
                "'.$product['ean13'].'",
                "'.$product['price'].'",
                "'.$product['weight'].'",
                "'.$product['depth'].'",
                "'.$product['width'].'",
                "'.$product['height'].'",
                "'.$product['active'].'",
                "'.$product['date_add'].'",
                "'.$product['date_upd'].'"
            );<br>
            ';
        }
        
        foreach($banco->assoc("SELECT * FROM ps_stock_available") as $product) {
            echo 'UPDATE oc_product SET quantity = "'.$product['quantity'].'" WHERE product_id = "'.$product['id_product'].'";<br>';
        }

        $caracteres = array("'", "\"");
        foreach($banco->assoc("SELECT * FROM ps_product_lang WHERE id_lang = 2") as $product) {
            
            $description = str_replace($caracteres, "", $product['description']);
            $name = str_replace($caracteres, "", $product['name']);
            echo 'INSERT INTO
                oc_product_description
            (
                product_id,
                language_id,
                name,
                description,
                meta_title
            ) VALUES (
                "'.$product['id_product'].'",
                "1",
                "'.$name.'",
                "'.$description.'",
                "'.$name.'"
            );<br>
            ';
        }
        echo "</pre>";
    }

    if($_GET['type'] == 'category') {
        echo "TRUNCATE TABLE oc_category;<br>";
        echo "TRUNCATE TABLE oc_category_path;<br>";
        echo "TRUNCATE TABLE oc_category_to_store;<br>";
        echo "TRUNCATE TABLE oc_category_description;<br>";

        foreach($banco->assoc("SELECT * FROM ps_category") as $categoria) {
            echo 'INSERT INTO 
                oc_category 
            (
                category_id, 
                parent_id, 
                sort_order, 
                status, 
                date_added, 
                date_modified
            ) VALUES (
                "'.$categoria['id_category'].'",
                "0",
                "'.$categoria['position'].'",
                "'.$categoria['active'].'",
                "'.$categoria['date_add'].'",
                "'.$categoria['date_upd'].'"
            );<br>';

            echo 'INSERT INTO 
                oc_category_path
            (
                category_id, 
                path_id,
                level
            ) VALUES (
                "'.$categoria['id_category'].'",
                "'.$categoria['id_category'].'",
                "1"
            );<br>';

            echo 'INSERT INTO 
                oc_category_to_store
            (
                category_id, 
                store_id
            ) VALUES (
                "'.$categoria['id_category'].'",
                "0"
            );<br>';
        }

        echo "<br><br>";

        foreach($banco->assoc("SELECT * FROM ps_category_lang WHERE id_lang = 2") as $categoria) {
            echo 'INSERT INTO 
                oc_category_description 
            (
                category_id, 
                language_id,
                name, 
                description, 
                meta_title, 
                meta_description, 
                meta_keyword
            ) VALUES (
                "'.$categoria['id_category'].'",
                "1",
                "'.$categoria['name'].'",
                "'.$categoria['description'].'",
                "'.$categoria['name'].'",
                "'.$categoria['meta_description'].'",
                "'.$categoria['meta_keywords'].'"
            );<br>';
        }

        echo "<br><br>";
    }
?>