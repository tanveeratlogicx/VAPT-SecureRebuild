┌─────────────────────────────────────────────────────────────┐
│  CLIENT INSTALLS VAPT PLUGIN                                 │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 1: RUNTIME DETECTION (No client input needed)          │
│  ├── Check SERVER_SOFTWARE header                           │
│  ├── Probe filesystem for config files                      │
│  ├── Test function availability                             │
│  └── Detect hosting provider                                │
│                                                             │
│  Result: "Detected Apache with .htaccess support"           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 2: CAPABILITY TESTING                                  │
│  ├── Can write to .htaccess?                                │
│  ├── mod_rewrite available?                                 │
│  ├── Cloudflare proxy enabled?                              │
│  └── PHP version sufficient?                                │
│                                                             │
│  Result: "Server-level blocking available"                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 3: OPTIMAL PLATFORM SELECTION                          │
│  ├── Priority 1: Cloudflare (if available)                  │
│  ├── Priority 2: Apache .htaccess (if writable)             │
│  └── Fallback: PHP Functions (always works)                 │
│                                                             │
│  Result: "Selected: Apache .htaccess + PHP fallback"        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 4: DEPLOYMENT                                          │
│  ├── Write optimized .htaccess rules                        │
│  ├── Install MU-Plugin for PHP fallback                     │
│  └── Configure both to work together                        │
│                                                             │
│  Result: "Protection active on 2 layers"                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 5: VERIFICATION                                        │
│  ├── Test blocking: ?author=1 → 403                         │
│  ├── Test legitimate: homepage → 200                        │
│  └── Measure performance impact                             │
│                                                             │
│  Result: "✓ All tests passed. Protection verified."         │
└─────────────────────────────────────────────────────────────┘