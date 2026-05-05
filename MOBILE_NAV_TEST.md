# Mobile Navigation Test Guide

## Testing the Mobile Navigation

The mobile navigation is fully implemented with three components:

### 1. Bottom Navigation Bar (Mobile Only)
**Location**: Fixed at the bottom of the screen on mobile devices (< 768px)

**Features**:
- 4 navigation tabs: Home, Products, Cart, Profile
- Active state highlighting (gold color)
- Cart badge showing item count
- Icon + label for each tab
- Smooth transitions

**To Test**:
1. Resize browser to mobile width (< 768px) or use mobile device
2. Bottom navigation should appear automatically
3. Click each tab to navigate
4. Add items to cart - badge should update
5. Active tab should be highlighted in gold

### 2. Mobile App Bar (Top)
**Location**: Sticky at the top on mobile devices

**Features**:
- Hamburger menu button (left)
- Anshu Jewels logo (center)
- Theme toggle button (right)
- Sticky positioning

**To Test**:
1. On mobile view, app bar should be visible at top
2. Click hamburger menu - drawer should slide in
3. Click theme toggle - should switch dark/light mode

### 3. Mobile Drawer Menu
**Location**: Slides in from left when hamburger is clicked

**Features**:
- Full menu with icons
- Home, Products, Profile, Orders links
- Login/Signup (when logged out)
- Logout option (when logged in)
- Close button
- Overlay background

**To Test**:
1. Click hamburger menu icon
2. Drawer should slide in from left
3. Background overlay should appear
4. Click any menu item to navigate
5. Click close button or overlay to close

## Responsive Breakpoints

- **Mobile**: < 768px
  - Shows: Bottom nav, App bar, Drawer menu
  - Hides: Desktop navbar
  
- **Tablet**: 768px - 1024px
  - Shows: Desktop navbar
  - Hides: Mobile navigation
  
- **Desktop**: > 1024px
  - Shows: Desktop navbar
  - Hides: Mobile navigation

## Cart Badge Sync

Both desktop and mobile cart badges are synchronized:
- Adding items updates both badges
- Badge shows total quantity
- Badge hides when cart is empty
- Red background for visibility

## Testing Steps

1. **Desktop View** (> 768px):
   ```
   - Top navbar visible
   - No bottom navigation
   - Cart badge in top navbar
   ```

2. **Mobile View** (< 768px):
   ```
   - App bar at top
   - Bottom navigation visible
   - Desktop navbar hidden
   - Cart badge in bottom nav
   ```

3. **Add to Cart**:
   ```
   - Click "Add to Cart" on any product
   - Both badges should update
   - Badge shows item count
   ```

4. **Navigation**:
   ```
   - Click tabs in bottom nav
   - Active tab highlights in gold
   - Icon scales slightly when active
   ```

5. **Drawer Menu**:
   ```
   - Click hamburger icon
   - Drawer slides in smoothly
   - Click overlay to close
   - Click close button to close
   ```

## Files Involved

- **HTML**: `includes/header.php` (lines 50-110, 121-151)
- **CSS**: `assets/css/mobile.css` (lines 6-225)
- **JavaScript**: `assets/js/main.js` (initMobileMenu, updateCartBadge)

## Visual Indicators

✅ Active tab: Gold color (#f59e0b)  
✅ Inactive tab: Gray color  
✅ Cart badge: Red background with white text  
✅ Smooth animations: 250ms transitions  

---

**Status**: ✅ Fully Implemented and Ready to Test
