<?php

function recloth_get_product_schema(PDO $pdo): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $columnsStmt = $pdo->query('SHOW COLUMNS FROM products');
    $columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN));

    $galleryTableStmt = $pdo->query("SHOW TABLES LIKE 'product_images'");
    $hasGalleryTable = (bool) $galleryTableStmt->fetchColumn();

    $cache = [
        'has_gender' => in_array('gender', $columns, true),
        'has_image' => in_array('image', $columns, true),
        'has_condition' => in_array('condition_status', $columns, true),
        'has_size' => in_array('size_label', $columns, true),
        'has_year' => in_array('production_year', $columns, true),
        'has_material' => in_array('material', $columns, true),
        'has_gallery_table' => $hasGalleryTable,
    ];

    return $cache;
}

function recloth_base_product_select(array $schema): string
{
    $discountCol = "COALESCE(p.discount_percent, 0)";

    return "
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.stock,
            " . $discountCol . " AS discount_percent,
            " . ($schema['has_image'] ? "IF(p.image LIKE 'uploads/%', CONCAT('/src/admin/', p.image), p.image)" : "''") . " AS image,
            " . ($schema['has_gender'] ? 'p.gender' : "''") . " AS gender,
            " . ($schema['has_condition'] ? 'p.condition_status' : "''") . " AS condition_status,
            " . ($schema['has_size'] ? 'p.size_label' : "''") . " AS size_label,
            " . ($schema['has_year'] ? 'p.production_year' : 'NULL') . " AS production_year,
            " . ($schema['has_material'] ? 'p.material' : "''") . " AS material,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
    ";
}

function recloth_fetch_products(PDO $pdo, array $options = []): array
{
    $schema = recloth_get_product_schema($pdo);
    $sql = recloth_base_product_select($schema);

    $search = trim((string) ($options['search'] ?? ''));
    $gender = strtolower(trim((string) ($options['gender'] ?? '')));
    $category = strtolower(trim((string) ($options['category'] ?? '')));
    $sort = trim((string) ($options['sort'] ?? 'terbaru'));
    $minPrice = $options['min_price'] ?? null;
    $maxPrice = $options['max_price'] ?? null;
    $limit = $options['limit'] ?? null;
    $featured = (bool) ($options['featured'] ?? false);

    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = 'p.name LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    if ($schema['has_gender'] && in_array($gender, ['pria', 'wanita'], true)) {
        $conditions[] = 'LOWER(p.gender) = :gender';
        $params[':gender'] = $gender;
    }

    if ($category !== '') {
        $conditions[] = 'LOWER(c.name) = :category';
        $params[':category'] = $category;
    }

    if (is_numeric($minPrice) && is_numeric($maxPrice)) {
        $conditions[] = 'p.price BETWEEN :min_price AND :max_price';
        $params[':min_price'] = min((float) $minPrice, (float) $maxPrice);
        $params[':max_price'] = max((float) $minPrice, (float) $maxPrice);
    } elseif (is_numeric($minPrice)) {
        $conditions[] = 'p.price >= :min_price';
        $params[':min_price'] = (float) $minPrice;
    } elseif (is_numeric($maxPrice)) {
        $conditions[] = 'p.price <= :max_price';
        $params[':max_price'] = (float) $maxPrice;
    }

    if ($featured) {
        $conditions[] = 'p.stock > 0';
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    if ($featured) {
        $sql .= ' ORDER BY COALESCE(p.discount_percent, 0) DESC, p.stock DESC, p.created_at DESC, p.id DESC';
    } elseif ($sort === 'harga_terendah') {
        $sql .= ' ORDER BY p.price ASC';
    } elseif ($sort === 'harga_tertinggi') {
        $sql .= ' ORDER BY p.price DESC';
    } else {
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
    }

    if (is_int($limit) && $limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recloth_fetch_product_by_id(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $schema = recloth_get_product_schema($pdo);
    $sql = recloth_base_product_select($schema) . ' WHERE p.id = :id LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    return $product ?: null;
}

function recloth_fetch_product_images(PDO $pdo, int $productId, string $primaryImage = ''): array
{
    $images = [];

    if (trim($primaryImage) !== '') {
        $images[] = trim($primaryImage);
    }

    $schema = recloth_get_product_schema($pdo);
    if (!$schema['has_gallery_table'] || $productId <= 0) {
        return array_values(array_unique($images));
    }

    $stmt = $pdo->prepare("SELECT IF(image_url LIKE 'uploads/%', CONCAT('/src/admin/', image_url), image_url) FROM product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC");
    $stmt->execute([':id' => $productId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rows as $url) {
        $url = trim((string) $url);
        if ($url !== '') {
            $images[] = $url;
        }
    }

    return array_values(array_unique($images));
}
