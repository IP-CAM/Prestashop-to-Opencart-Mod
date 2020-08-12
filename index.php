<?php
    require_once './banco.php';
    $prestashop = new banco('', '', '', '');
    $opencart = new banco('', '', '', '');

    $file = 'importacao.sql';
    header("Content-Type: application/save");
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header("Content-Transfer-Encoding: binary");
    header('Expires: 0');
    header('Pragma: no-cache');

    echo 'TRUNCATE TABLE oc_customer;';
    echo 'TRUNCATE TABLE `oc_product_option`;';
    echo 'TRUNCATE TABLE `oc_product_option_value`;';
    echo 'TRUNCATE TABLE `oc_option`;';
    echo 'TRUNCATE TABLE `oc_option_value`;';
    echo 'TRUNCATE TABLE `oc_option_description`;';
    echo 'TRUNCATE TABLE `oc_option_value_description`;';
    echo 'TRUNCATE TABLE ps_category_product;';
    echo 'TRUNCATE TABLE oc_product_image;';
    echo 'TRUNCATE TABLE oc_product_to_store;';
    echo 'TRUNCATE TABLE oc_product;';
    echo 'TRUNCATE TABLE oc_product_description;';
    echo "TRUNCATE TABLE oc_category;";
    echo "TRUNCATE TABLE oc_category_path;";
    echo "TRUNCATE TABLE oc_category_to_store;";
    echo "TRUNCATE TABLE oc_category_description;";

    foreach($prestashop->assoc("SELECT * FROM `ps_customer`") as $cliente) {
        $sql = "SELECT * FROM `ps_address` WHERE id_customer = '".$cliente['id_customer']."' AND active = 1 GROUP BY id_customer";
        $address = $prestashop->assoc($sql);
        echo 'INSERT INTO 
            `oc_customer` 
        (
            customer_id, 
            customer_group_id,
            firstname, 
            lastname, 
            cpf,
            email,
            telephone,
            password,
            newsletter,
            address_id,
            status,
            approved
        ) VALUES (
            "'.$cliente['id_customer'].'",
            "1",
            "'.$cliente['firstname'].'",
            "'.$cliente['lastname'].'",
            "'.$cliente['cpf_cnpj'].'",
            "'.$cliente['email'].'",
            "'.$address[0]['phone'].'",
            "'.$cliente['passwd'].'",
            "'.$cliente['newsletter'].'",
            "'.$address[0]['id_address'].'",
            "'.$cliente['active'].'",
            "1"
        );';
    }

    if ($_GET['type'] == 'address') {
        $caracteres = array("'", "\"");
        echo 'TRUNCATE TABLE oc_address;';
        foreach($prestashop->assoc('SELECT * FROM `ps_address`') as $endereco) {
            $sql = "SELECT iso_code FROM ps_state WHERE id_state = '".$endereco['id_state']."'";
            $state = $prestashop->assoc($sql);
            $sql2 = "SELECT * FROM oc_zone WHERE code = '".$state[0]['iso_code']."' AND country_id = '30'";
            $state2 = $opencart->assoc($sql2);
            $address1 = str_replace($caracteres, "", $endereco['address1']);
            $address2 = str_replace($caracteres, "", $endereco['address2']);
            echo 'INSERT INTO 
                oc_address 
            (
                address_id,
                customer_id,
                firstname,
                lastname,
                company,
                address_1,
                address_2,
                city,
                postcode,
                country_id,
                zone_id
            ) VALUES (
                "'.$endereco['id_address'].'",
                "'.$endereco['id_customer'].'",
                "'.$endereco['firstname'].'",
                "'.$endereco['lastname'].'",
                "'.$endereco['company'].'",
                "'.$address1.'",
                "'.$address2.'",
                "'.$endereco['city'].'",
                "'.$endereco['postcode'].'",
                "0",
                "'.$state2[0]['zone_id'].'"
            );';
        }
    }

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
        
    foreach($prestashop->assoc($sql) as $i => $opcao) {
        echo "INSERT INTO oc_product_option (product_id, option_id) VALUES ('".$opcao['id_product']."', '".$opcao['id_attribute_group']."');";
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
        
        foreach($prestashop->assoc($sql_i) as $opcaoi) {
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
                );";
        }
    }
        
    foreach($prestashop->assoc("SELECT * FROM `ps_attribute_group`") as $opcao) {
        echo "INSERT INTO oc_option (option_id, type, sort_order) VALUES ('".$opcao["id_attribute_group"]."', '".$opcao["group_type"]."', '".$opcao["position"]."');";
    }

    foreach($prestashop->assoc("SELECT * FROM `ps_attribute`") as $opcao) {
        echo "INSERT INTO oc_option_value (option_value_id, option_id, sort_order) VALUES ('".$opcao['id_attribute']."', '".$opcao['id_attribute_group']."', '".$opcao['position']."');";
    }

    foreach($prestashop->assoc("SELECT * FROM `ps_attribute_group_lang` WHERE id_lang = 2") as $opcao) {
        echo "INSERT INTO oc_option_description (option_id, language_id, name) VALUES ('".$opcao['id_attribute_group']."', '1', '".$opcao['name']."');";
    }

    foreach($prestashop->assoc("SELECT l.*, a.id_attribute_group, a.id_attribute as id FROM `ps_attribute_lang` as l INNER JOIN ps_attribute as a on l.id_attribute = a.id_attribute WHERE l.id_lang = 2") as $opcao) {
        echo "INSERT INTO oc_option_value_description (option_value_id, language_id, option_id, name) VALUES ('".$opcao['id']."', '1', '".$opcao['id_attribute_group']."', '".$opcao['name']."');";
    }
        
    foreach ($prestashop->assoc("SELECT * FROM `ps_category_product`") as $cp) {
        echo 'INSERT INTO oc_product_to_category (product_id, category_id) VALUES ("'.$cp['id_product'].'", "'.$cp['id_category'].'");';
    }
    
    foreach ($prestashop->assoc("SELECT * FROM `ps_image`") as $image) {
        $id_image = str_split($image['id_image']);
        $id_image = implode("/", $id_image);
        if ($image['cover'] == 1) {
            echo 'UPDATE oc_product SET image = "p/'.$id_image.'/'.$image['id_image'].'.jpg" WHERE product_id = "'.$image['id_product'].'";';
        } else {
            echo 'INSERT INTO oc_product_image (product_id, image) VALUES ("'.$image['id_product'].'", "p/'.$id_image.'/'.$image['id_image'].'.jpg");';
        }
    }

    foreach($prestashop->assoc("SELECT * FROM ps_product") as $product) {
        echo 'INSERT INTO
            oc_product_to_store
        (
            product_id,
            store_id
        ) VALUES (
            "'.$product['id_product'].'",
            "0"
        );
        ';
    }

    foreach($prestashop->assoc("SELECT * FROM ps_product") as $product) {
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
        );
        ';
    }
        
    foreach($prestashop->assoc("SELECT * FROM ps_stock_available") as $product) {
        echo 'UPDATE oc_product SET quantity = "'.$product['quantity'].'" WHERE product_id = "'.$product['id_product'].'";';
    }

    $caracteres = array("'", "\"");
    foreach($prestashop->assoc("SELECT * FROM ps_product_lang WHERE id_lang = 2") as $product) {
        
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
        );
        ';
    }

    foreach($prestashop->assoc("SELECT * FROM ps_category") as $categoria) {
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
        );';

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
        );';

        echo 'INSERT INTO 
            oc_category_to_store
        (
            category_id, 
            store_id
        ) VALUES (
            "'.$categoria['id_category'].'",
            "0"
        );';
    }

    foreach($prestashop->assoc("SELECT * FROM ps_category_lang WHERE id_lang = 2") as $categoria) {
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
        );';
    }
?>