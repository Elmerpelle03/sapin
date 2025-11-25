-- ============================================
-- FINAL FIX: Correctly identify PILLOWS vs PILLOWCASES
-- ============================================

-- STEP 1: Remove crushed foam from ALL products first (clean slate)
DELETE pm FROM product_materials pm
JOIN materials m ON pm.material_id = m.material_id
WHERE m.material_name = 'Crushed Foam with Fiber';

-- STEP 2: Add crushed foam ONLY to actual PILLOWS
-- A product is a PILLOW if:
--   - Has "pillow" in the name
--   - Does NOT have "pillowcase" or "pillow case" anywhere in the name
--   - Does NOT have "case" right after "pillow" (e.g., "pillow case")

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67
FROM products p
CROSS JOIN materials m
WHERE m.material_name = 'Crushed Foam with Fiber'
  AND (
    -- Match products with "pillow" but NOT "pillowcase" or "pillow case"
    (LOWER(p.product_name) LIKE '%pillow%' 
     AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
     AND LOWER(p.product_name) NOT LIKE '%pillow case%'
     AND LOWER(p.product_name) NOT LIKE '%case%')
    OR
    -- OR match products that explicitly say "pillow with" (pillow bundles)
    (LOWER(p.product_name) LIKE '%pillow with%')
  )
  AND NOT EXISTS (
    SELECT 1 FROM product_materials pm2
    WHERE pm2.product_id = p.product_id 
      AND pm2.material_id = m.material_id
  );

-- STEP 3: Verify - Show which products have foam
SELECT 
    p.product_id,
    p.product_name,
    CASE 
        WHEN pm.id IS NOT NULL THEN '✅ HAS FOAM (Pillow)'
        ELSE '❌ NO FOAM (Pillowcase/Other)'
    END as foam_status,
    COUNT(pm2.id) as total_materials
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id 
    AND pm.material_id = (SELECT material_id FROM materials WHERE material_name = 'Crushed Foam with Fiber')
LEFT JOIN product_materials pm2 ON p.product_id = pm2.product_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
GROUP BY p.product_id, p.product_name, pm.id
ORDER BY foam_status, p.product_name;

-- Expected results:
-- ✅ "Pillow free 1 pillowcase" → HAS FOAM (it's a pillow with free case)
-- ✅ "Pillow hotdog" → HAS FOAM (it's a pillow)
-- ❌ "1 Large funnel + 2 Large Zipper Pillowcase" → NO FOAM (it's just cases)
-- ❌ "Pillowcase single" → NO FOAM (it's just a case)
