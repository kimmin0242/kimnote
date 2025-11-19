# Quick Deployment Guide - Engine Oil Parts Fix

## 🚀 Quick Start (5 Minutes)

### Option 1: Copy Files to Synology NAS

```bash
# 1. Package the necessary files
cd /home/user/webapp
tar -czf hyundai-parts-fix.tar.gz \
  api/get_trims.php \
  api/get_fuel_types.php \
  api/get_engines.php \
  api/get_models.php \
  api/search_parts_advanced.php \
  index.php

# 2. Transfer to Synology (replace with your NAS IP)
scp hyundai-parts-fix.tar.gz admin@YOUR_NAS_IP:/volume1/web/hyundai-parts/

# 3. SSH to Synology and extract
ssh admin@YOUR_NAS_IP
cd /volume1/web/hyundai-parts/
tar -xzf hyundai-parts-fix.tar.gz
rm hyundai-parts-fix.tar.gz

# 4. Set correct permissions
chmod 644 api/*.php index.php
chown http:http api/*.php index.php
```

### Option 2: Manual Copy via File Manager

1. **Download these files from the sandbox**:
   - `api/get_trims.php`
   - `api/get_fuel_types.php`
   - `api/get_engines.php`
   - `api/get_models.php`
   - `api/search_parts_advanced.php`
   - `index.php`

2. **Upload to Synology NAS**:
   - Navigate to File Station
   - Go to `/web/hyundai-parts/`
   - Upload files to their respective directories

3. **Set Permissions** (via DSM Terminal):
   ```bash
   cd /volume1/web/hyundai-parts
   chmod 644 api/*.php index.php
   ```

## ✅ Verify Deployment

### 1. Test the Main Page
Open in browser: `http://YOUR_NAS_IP/hyundai-parts/`

Expected result: You should see the new 5-level search interface

### 2. Test G80 RG3 Search
1. Select **차명**: G80
2. Select **세대**: RG3 (3세대)
3. Click **검색하기**
4. Switch to **엔진류** tab

Expected results:
- ✅ 엔진오일 (대) - Part# 05100-2S400
- ✅ 엔진오일 (소) - Part# 05100-2S100
- ✅ Other engine-related parts

### 3. Check Browser Console
Press F12 and check for errors. Should see:
```
✅ No JavaScript errors
✅ API calls returning 200 OK
✅ Parts data loading correctly
```

## 🔍 Troubleshooting

### Issue: 404 on API calls
**Solution**: Check file paths and permissions
```bash
cd /volume1/web/hyundai-parts
ls -la api/
# Should show: get_trims.php, get_fuel_types.php, etc.
```

### Issue: Empty search results
**Solution**: Check database connection
```bash
cd /volume1/web/hyundai-parts
php -r "
require 'config/db.php';
\$stmt = \$pdo->query('SELECT COUNT(*) FROM genuine_parts');
echo 'Parts count: ' . \$stmt->fetchColumn() . PHP_EOL;
"
```

### Issue: Engine oil still missing
**Solution**: Verify database has the parts
```bash
mysql -u hyundai_user -p hyundai_parts
# Enter password: Hyundai@2025

SELECT part_number, product_name, compatible_engines 
FROM genuine_parts 
WHERE product_name LIKE '%엔진오일%';
```

Expected output:
```
+-------------+----------------+--------------------+
| part_number | product_name   | compatible_engines |
+-------------+----------------+--------------------+
| 05100-2S400 | 엔진오일 (대)   | 전체                |
| 05100-2S100 | 엔진오일 (소)   | 전체                |
+-------------+----------------+--------------------+
```

## 📱 Mobile Testing

1. Open on mobile browser
2. Check responsive design works
3. Verify dropdowns are touch-friendly
4. Test search functionality

## 🎯 What Changed?

### Before
```
User searches G80 RG3
└─→ Engine oil parts missing ❌
```

### After
```
User searches G80 RG3
├─→ Gets vehicle engines
├─→ Matches parts with '전체' OR specific engines
└─→ Engine oil parts appear ✅
```

## 📋 Rollback Plan

If anything goes wrong, restore from backup:

```bash
# 1. Save current state (just in case)
cd /volume1/web/hyundai-parts
cp -r api api_backup_$(date +%Y%m%d)
cp index.php index_backup_$(date +%Y%m%d).php

# 2. If needed, restore database
# mysql -u hyundai_user -p hyundai_parts < backup_YYYYMMDD.sql
```

## ✨ Success Indicators

- [x] No PHP errors in Apache logs
- [x] All API endpoints return valid JSON
- [x] Search dropdowns populate correctly
- [x] Engine oil parts appear in G80 RG3 search
- [x] Tabbed results display works
- [x] Mobile view is responsive

## 📞 Need Help?

Check the detailed documentation:
- `DEPLOYMENT_SUMMARY.md` - Full deployment guide
- `FIX_EXPLANATION.md` - Technical explanation
- Apache logs: `/var/log/apache2/error_log`
- PHP logs: Check DSM → Log Center

---

**Deployment Time**: ~5 minutes  
**Difficulty**: Easy  
**Risk**: Low (backward compatible)  
**Recommended**: Deploy during low-traffic hours
