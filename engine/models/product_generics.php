<?php

	include_once('product_minprice.php');

	function get_generics($product) {
		$query = "SELECT `products`.`id`, `products`.`name`, `products`.`url` FROM `products`, `items`, `generics` WHERE `generics`.`product` = ".$product['id']." AND `items`.`id` = `generics`.`item` AND `items`.`product` = `products`.`id` AND `items`.`price` != 0 AND `products`.`published` = 1 GROUP BY `products`.`id`";
		$data = database_query($query);
		$generics = array();
		while ($generic = mysql_fetch_assoc($data)) {
			$generics[] = $generic;
		}

		if (count($generics) > 0) {
			join_minprices($generics);
			usort($generics, 'sort_by_price');
			array_walk($generics, 'set_price_colors', array(
				'generics' => $generics,
				'product_price' => $product['price']
			));
		}

		return $generics;
	}

	function sort_by_price($a, $b) {
		if ($a['price'] == $b['price']) return 0;
		return ($a['price'] < $b['price']) ? -1 : 1;
	}

	function compare_price($price, $generics, $product_price) {
		if (count($generics) < 2) {
			return 'cheap';
		}

		$min = $generics[0]['price'];
		$max = $generics[count($generics) - 1]['price'];

		if ($product_price < $min) $min = $product_price;
		elseif ($product_price > $max) $max = $product_price;

		$relative = ($price - $min) / ($max - $min);

		if ($price < 5 || $relative < 0.1) return 'cheap';
		elseif ($relative < 0.2) return 'good';
		elseif ($price < 50 || $relative < 0.4) return 'acceptable';
		elseif ($price < 100 || $relative < 0.7) return 'pricey';
		else return 'overpriced';
	}

	function set_price_colors(&$item, $key, $data) {
		$item['color'] = compare_price($item['price'], $data['generics'], $data['product_price']);
	}