<?php
function toCamel($row) {
    if ($row === null) return null;
    $result = [];

    foreach ($row as $key => $value) {
        if (in_array($key, ['location_lat', 'location_lng'])) {
            continue;
        }
        if ($key === 'services_for') {
            $result['servicesFor'] = ($value && $value !== '') ? explode(',', $value) : [];
            continue;
        }
        $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
        $result[$camelKey] = $value;
    }

    if (array_key_exists('location_lat', $row) || array_key_exists('location_lng', $row)) {
        $result['location'] = [
            'lat' => $row['location_lat'] !== null ? (float)$row['location_lat'] : null,
            'lng' => $row['location_lng'] !== null ? (float)$row['location_lng'] : null
        ];
    }

    if (array_key_exists('rec_value', $row) && $row['rec_value'] !== null) {
        $result['recurrence'] = [
            'value' => (int)$row['rec_value'],
            'unit' => $row['rec_unit'] ?? '',
            'repeatFrom' => $row['repeat_from'] ?? ''
        ];
    }

    return $result;
}

function toCamelArray($rows) {
    return array_map('toCamel', $rows);
}

function toSnake($data) {
    $result = [];

    foreach ($data as $key => $value) {
        if ($key === 'location' && is_array($value)) {
            if (isset($value['lat'])) $result['location_lat'] = $value['lat'];
            if (isset($value['lng'])) $result['location_lng'] = $value['lng'];
            continue;
        }
        if ($key === 'recurrence' && is_array($value)) {
            if (isset($value['value'])) $result['rec_value'] = $value['value'];
            if (isset($value['unit'])) $result['rec_unit'] = $value['unit'];
            if (isset($value['repeatFrom'])) $result['repeat_from'] = $value['repeatFrom'];
            continue;
        }
        if ($key === 'servicesFor') {
            $result['services_for'] = is_array($value) ? implode(',', $value) : (string)$value;
            continue;
        }
        $snakeKey = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key));
        $result[$snakeKey] = $value;
    }

    return $result;
}
