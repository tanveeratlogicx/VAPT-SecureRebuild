// Client Dashboard Entry Point
// Phase 6 Implementation - IDE Workbench Redesign
(function () {
  console.log('VAPT Secure: client.js loaded');
  if (typeof wp === 'undefined') return;

  const { render, useState, useEffect, useMemo, Fragment, createElement: el } = wp.element || {};
  const { Button, ToggleControl, Spinner, Notice, Card, CardBody, CardHeader, CardFooter, Icon, Tooltip, Modal } = wp.components || {};
  const settings = window.vaptSecureSettings || {};
  const apiFetch = wp.apiFetch;
  const { __, sprintf } = wp.i18n || {};

  const GeneratedInterface = window.VAPTSECURE_GeneratedInterface || window.vapt_GeneratedInterface;

  const ClientDashboard = () => {
    const [features, setFeatures] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [error, setError] = useState(null);
    const domain = settings.currentDomain || window.location.hostname;
    const [activeTab, setActiveTab] = useState('all'); // 'all', 'stats', or severity level
    const [saveStatus, setSaveStatus] = useState(null);
    const [enforceStatusMap, setEnforceStatusMap] = useState({});
    const [verifFeature, setVerifFeature] = useState(null);

    const [securityStats, setSecurityStats] = useState({
      total_blocks: 0,
      blocks_24h: 0,
      top_risk: 'None',
      active_enforcements: 0
    });
    const [securityLogs, setSecurityLogs] = useState([]);
    const [logsLoading, setLogsLoading] = useState(false);

    // Fetch Data
    const fetchData = (refresh = false) => {
      if (refresh) setIsRefreshing(true);
      else setLoading(true);

      const path = `vaptsecure/v1/features?scope=client&domain=${encodeURIComponent(domain)}`;
      apiFetch({ path })
        .then(data => {
          const uniqueFeatures = Array.from(new Map((data.features || []).map(item => [item.key, item])).values());
          setFeatures(uniqueFeatures);
          setLoading(false);
          setIsRefreshing(false);
        })
        .catch(err => {
          setError(err.message || 'Failed to load features');
          setLoading(false);
          setIsRefreshing(false);
        });
    };

    const fetchSecurityInsights = () => {
      // Fetch Stats
      apiFetch({ path: 'vaptsecure/v1/security/stats' })
        .then(data => setSecurityStats(data))
        .catch(err => console.error('Failed to fetch security stats:', err));

      // Fetch Logs
      setLogsLoading(true);
      apiFetch({ path: 'vaptsecure/v1/security/logs?limit=10' })
        .then(data => {
          setSecurityLogs(data || []);
          setLogsLoading(false);
        })
        .catch(err => {
          console.error('Failed to fetch security logs:', err);
          setLogsLoading(false);
        });
    };

    useEffect(() => {
      fetchData();
      fetchSecurityInsights();

      // Real-time Polling: 30 seconds
      const interval = setInterval(fetchSecurityInsights, 30000);
      return () => clearInterval(interval);
    }, []);

    const updateFeature = (key, data, successMsg, silent = false) => {
      setFeatures(prev => prev.map(f => f.key === key ? { ...f, ...data } : f));
      if (!silent) setSaveStatus({ message: __('Saving...', 'vaptsecure'), type: 'info' });

      return apiFetch({
        path: 'vaptsecure/v1/features/update',
        method: 'POST',
        data: { key, ...data }
      })
        .then((res) => {
          if (!silent) setSaveStatus({ message: successMsg || __('Saved', 'vaptsecure'), type: 'success' });
          return res;
        })
        .catch(err => {
          if (!silent) setSaveStatus({ message: __('Save Failed', 'vaptsecure'), type: 'error' });
          throw err;
        });
    };

    // Filtered Features
    const releasedFeatures = useMemo(() => {
      return features.filter(f => {
        const s = f.normalized_status || (f.status ? f.status.toLowerCase() : '');
        return ['release', 'implemented'].includes(s);
      });
    }, [features]);

    const filteredFeatures = useMemo(() => {
      if (activeTab === 'all' || activeTab === 'stats') return releasedFeatures;
      return releasedFeatures.filter(f => (f.severity || '').toLowerCase() === activeTab);
    }, [releasedFeatures, activeTab]);

    const severityConfigs = useMemo(() => {
      const counts = { all: releasedFeatures.length };
      const severities = [];

      releasedFeatures.forEach(f => {
        const s = (f.severity || 'low').toLowerCase();
        if (!counts[s]) {
          counts[s] = 0;
          severities.push(s);
        }
        counts[s]++;
      });

      const configMap = {
        critical: { label: __('Critical', 'vaptsecure'), icon: 'warning', color: '#dc2626' },
        high: { label: __('High Severity', 'vaptsecure'), icon: 'warning', color: '#ea580c' },
        medium: { label: __('Medium', 'vaptsecure'), icon: 'flag', color: '#2271b1' },
        low: { label: __('Low Severity', 'vaptsecure'), icon: 'yes-alt', color: '#64748b' }
      };

      const items = [{ id: 'all', label: __('All Features', 'vaptsecure'), icon: 'shield', count: counts.all }];

      // Add existing severities dynamically
      ['critical', 'high', 'medium', 'low'].forEach(key => {
        if (counts[key] > 0) {
          items.push({ id: key, ...configMap[key], count: counts[key] });
        }
      });

      return items;
    }, [releasedFeatures]);

    // Stats Dashboard Component
    const StatsDashboard = () => {
      const stats = [
        { label: __('Total Protection Rules', 'vaptsecure'), value: releasedFeatures.length, icon: 'shield-alt', color: '#2271b1' },
        { label: __('Active Enforcements', 'vaptsecure'), value: securityStats.active_enforcements, icon: 'yes', color: '#10b981' },
        { label: __('Security Events (Total)', 'vaptsecure'), value: securityStats.total_blocks, icon: 'visibility', color: '#f59e0b' },
        { label: __('Risk Blocked (24h)', 'vaptsecure'), value: securityStats.blocks_24h, icon: 'warning', color: '#dc2626' }
      ];

      return el('div', { className: 'vapt-stats-grid', style: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '20px', marginBottom: '30px' } },
        stats.map((s, idx) => el('div', { key: idx, className: 'vapt-stat-card premium', style: { background: 'white', padding: '20px', borderRadius: '12px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' } }, [
          el('div', { style: { display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '10px' } }, [
            el(Icon, { icon: s.icon, size: 20, style: { color: s.color } }),
            el('span', { style: { fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.05em' } }, s.label)
          ]),
          el('div', { style: { fontSize: '24px', fontWeight: 800, color: '#1e293b' } }, s.value)
        ]))
      );
    };

    const LiveSecurityLogs = () => {
      return el('div', { className: 'vapt-log-card', style: { background: 'white', borderRadius: '12px', border: '1px solid #e2e8f0', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' } }, [
        el('div', { style: { padding: '15px 20px', borderBottom: '1px solid #e2e8f0', background: '#f8fafc', display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }, [
          el('h3', { style: { margin: 0, fontSize: '14px', fontWeight: 700, color: '#1e293b' } }, __('Live Security Log', 'vaptsecure')),
          el('div', { style: { display: 'flex', alignItems: 'center', gap: '15px' } }, [
            logsLoading && el(Spinner, { size: 16 }),
            el('span', { className: 'vapt-live-indicator', style: { display: 'flex', alignItems: 'center', gap: '6px', fontSize: '11px', color: '#10b981', fontWeight: 600 } }, [
              el('span', { className: 'pulse-dot' }), __('Live Monitoring Active', 'vaptsecure')
            ])
          ])
        ]),
        el('table', { style: { width: '100%', borderCollapse: 'collapse' } }, [
          el('thead', null, el('tr', null, [
            [__('Time', 'vaptsecure'), __('Risk ID', 'vaptsecure'), __('Event', 'vaptsecure'), __('Source IP', 'vaptsecure'), __('Status', 'vaptsecure')].map(h => el('th', { key: h, style: { textAlign: 'left', padding: '12px 20px', fontSize: '11px', fontWeight: 700, color: '#64748b', background: '#fcfcfd', textTransform: 'uppercase' } }, h))
          ])),
          el('tbody', null, securityLogs.length === 0 ? el('tr', null, el('td', { colSpan: 5, style: { padding: '40px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' } }, __('No security events recorded yet.', 'vaptsecure'))) :
            securityLogs.map((log, i) => {
              const details = JSON.parse(log.details || '{}');
              return el('tr', { key: log.id, style: { borderBottom: '1px solid #f1f5f9' } }, [
                el('td', { style: { padding: '12px 20px', fontSize: '12px', color: '#64748b' } }, log.created_at),
                el('td', { style: { padding: '12px 20px', fontSize: '12px', fontWeight: 700, color: '#1e293b' } }, log.feature_key),
                el('td', { style: { padding: '12px 20px', fontSize: '12px', color: '#475569' } }, details.type || log.event_type),
                el('td', { style: { padding: '12px 20px', fontSize: '12px', color: '#64748b', fontFamily: 'monospace' } }, log.ip_address),
                el('td', { style: { padding: '12px 20px' } }, el('span', { className: 'status-badge', style: { background: '#fee2e2', color: '#dc2626' } }, log.event_type))
              ]);
            }))
        ])
      ]);
    };

    if (loading) return el('div', { className: 'vapt-loading-full', style: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '400px' } }, [el(Spinner), el('p', { style: { marginTop: '15px', color: '#64748b' } }, __('Initializing Secure Environment...', 'vaptsecure'))]);
    if (error) return el(Notice, { status: 'error', isDismissible: false }, error);

    const activeDomain = settings.currentDomain || window.location.hostname;

    return el('div', { className: 'vapt-client-root premium-layout', style: { display: 'flex', minHeight: 'calc(100vh - 120px)', background: '#f8fafc' } }, [
      // Sidebar
      el('aside', { className: 'vapt-client-sidebar', style: { width: '280px', background: 'white', borderRight: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column' } }, [

        el('div', { className: 'sidebar-menu', style: { padding: '20px', flexGrow: 1 } }, [
          el('div', { style: { fontSize: '10px', fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '15px', paddingLeft: '10px' } }, __('Protection Status')),
          severityConfigs.map(item => el('button', {
            key: item.id,
            onClick: () => setActiveTab(item.id),
            className: `menu-item ${activeTab === item.id ? 'active' : ''}`,
            style: {
              width: '100%', border: 'none', background: activeTab === item.id ? '#eff6ff' : 'transparent',
              color: activeTab === item.id ? '#1d4ed8' : '#475569',
              padding: '12px 15px', borderRadius: '8px', cursor: 'pointer', textAlign: 'left',
              display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '4px',
              transition: 'all 0.2s ease'
            }
          }, [
            el('span', { style: { display: 'flex', alignItems: 'center', gap: '10px', fontSize: '14px', fontWeight: activeTab === item.id ? 700 : 500 } }, [
              el(Icon, { icon: item.icon, size: 18, style: { color: activeTab === item.id ? '#1d4ed8' : (item.color || '#94a3b8') } }),
              item.label
            ]),
            item.count > 0 && el('span', { style: { fontSize: '10px', background: activeTab === item.id ? '#1d4ed8' : '#e2e8f0', color: activeTab === item.id ? 'white' : '#64748b', padding: '2px 8px', borderRadius: '10px', fontWeight: 700 } }, item.count)
          ])),

          el('div', { style: { margin: '30px 0 15px', borderTop: '1px solid #f1f5f9', paddingTop: '20px' } }),
          el('div', { style: { fontSize: '10px', fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '15px', paddingLeft: '10px' } }, __('Security Insights')),

          el('button', {
            onClick: () => setActiveTab('stats'),
            className: `menu-item ${activeTab === 'stats' ? 'active' : ''}`,
            style: {
              width: '100%', border: 'none', background: activeTab === 'stats' ? 'linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%)' : 'transparent',
              color: activeTab === 'stats' ? 'white' : '#475569',
              padding: '12px 15px', borderRadius: '8px', cursor: 'pointer', textAlign: 'left',
              display: 'flex', alignItems: 'center', gap: '10px', transition: 'all 0.2s ease', boxShadow: activeTab === 'stats' ? '0 4px 12px rgba(29, 78, 216, 0.25)' : 'none'
            }
          }, [
            el(Icon, { icon: 'chart-bar', size: 18 }),
            el('span', { style: { fontSize: '14px', fontWeight: activeTab === 'stats' ? 700 : 500 } }, __('Stats & Live Logs', 'vaptsecure'))
          ])
        ])
      ]),

      // Main Content
      el('main', { className: 'vapt-client-main', style: { flexGrow: 1, padding: '40px', overflowY: 'auto' } }, [
        activeTab === 'stats' ? [
          el('h1', { style: { margin: '0 0 30px 0', fontSize: '24px', fontWeight: 800, color: '#1e293b' } }, __('Security Overview')),
          el(StatsDashboard),
          el(LiveSecurityLogs)
        ] : [
          el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '30px' } }, [
            el('div', null, [
              el('h1', { style: { margin: 0, fontSize: '24px', fontWeight: 800, color: '#1e293b', lineHeight: '1.2' } }, [
                __('VAPT Admin Dashboard', 'vaptsecure'),
                el('span', { style: { fontSize: '16px', fontWeight: 600, color: '#64748b', marginLeft: '10px', verticalAlign: 'middle' } }, `v${settings.pluginVersion}`),
                el('div', { style: { fontSize: '14px', fontWeight: 500, color: '#64748b', marginTop: '5px' } }, sprintf(__('Active Protection for %s', 'vaptsecure'), domain))
              ])
            ]),
            el(Button, {
              isSecondary: true,
              onClick: () => fetchData(true),
              isBusy: isRefreshing,
              icon: 'update',
              style: { borderRadius: '8px' }
            }, __('Sync Protection', 'vaptsecure'))
          ]),

          el('div', { className: 'vapt-feature-list', style: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '25px' } },
            filteredFeatures.length === 0 ? el('div', { style: { padding: '60px', textAlign: 'center', background: 'white', borderRadius: '12px', border: '1px dashed #cbd5e1' } }, [
              el(Icon, { icon: 'shield', size: 48, style: { color: '#e2e8f0', marginBottom: '15px' } }),
              el('p', { style: { color: '#64748b', fontSize: '16px' } }, __('No released protections found for this level.', 'vaptsecure'))
            ]) :
              filteredFeatures.map(f => renderFeatureCard(f, updateFeature, setVerifFeature))
          )
        ]
      ]),

      // Portal for saving status
      saveStatus && el('div', {
        style: {
          position: 'fixed', bottom: '30px', right: '30px',
          background: saveStatus.type === 'error' ? '#9b1c1c' : (saveStatus.type === 'success' ? '#059669' : '#1e40af'),
          color: 'white', padding: '12px 24px', borderRadius: '12px', boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
          zIndex: 9999, fontWeight: '700', fontSize: '13px', display: 'flex', alignItems: 'center', gap: '10px'
        }
      }, [
        el(Icon, { icon: saveStatus.type === 'error' ? 'warning' : (saveStatus.type === 'success' ? 'yes' : 'update'), size: 18 }),
        saveStatus.message
      ]),

      // Portal for Manual Verification
      verifFeature && el(Modal, {
        title: sprintf(__('Verification Protocol: %s', 'vaptsecure'), verifFeature.label),
        onRequestClose: () => setVerifFeature(null),
        className: 'vapt-verif-modal'
      }, (() => {
        const f = verifFeature;
        const schema = typeof f.generated_schema === 'string' ? JSON.parse(f.generated_schema) : (f.generated_schema || { controls: [] });
        const protocol = f.test_method || '';
        const checklist = typeof f.verification_steps === 'string' ? JSON.parse(f.verification_steps) : (f.verification_steps || []);

        return el('div', { style: { padding: '20px' } }, [
          el('h4', null, __('Manual Protocol')),
          el('p', null, protocol || __('No specific manual protocol defined.')),
          checklist.length > 0 && el('div', null, [
            el('h4', null, __('Evidence Checklist')),
            el('ul', null, checklist.map((s, i) => el('li', { key: i }, s)))
          ]),
          el(Button, { isPrimary: true, onClick: () => setVerifFeature(null) }, __('Close'))
        ]);
      })())
    ]);
  };

  const renderFeatureCard = (f, updateFeature, setVerifFeature) => {
    const schema = typeof f.generated_schema === 'string' ? JSON.parse(f.generated_schema) : (f.generated_schema || { controls: [] });

    const implControls = schema.controls ? schema.controls.filter(c =>
      !['test_action', 'risk_indicators', 'assurance_badges', 'test_checklist', 'evidence_list'].includes(c.type) &&
      !c.label?.toLowerCase().includes('notes')
    ) : [];

    const automControls = schema.controls ? schema.controls.filter(c =>
      c.type === 'test_action' && c.label !== 'Site Integrity Check'
    ) : [];

    return el(Card, { key: f.key, className: 'vapt-feature-card', style: { borderRadius: '12px', border: '1px solid #e2e8f0' } }, [
      el(CardHeader, { style: { borderBottom: '1px solid #f1f5f9', padding: '15px 25px' } }, [
        el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' } }, [
          el('div', null, [
            el('h3', { style: { margin: 0, fontSize: '16px', fontWeight: 700, display: 'flex', alignItems: 'center', gap: '8px' } }, [
              f.label,
              f.severity && el('span', { className: `severity-pill ${f.severity.toLowerCase()}` }, f.severity),
              f.include_manual_protocol && el(Tooltip, { text: __('View Verification Protocol', 'vaptsecure') },
                el(Button, { isLink: true, onClick: () => setVerifFeature(f), style: { height: 'auto', padding: 0 } },
                  el(Icon, { icon: 'excerpt-view', size: 18, style: { color: '#64748b' } })
                )
              )
            ]),
            f.description && el('p', { style: { margin: '5px 0 0 0', fontSize: '12px', color: '#64748b' } }, f.description)
          ]),
          el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } }, [
            el('span', { style: { fontSize: '12px', fontWeight: 600 } }, __('Enforce')),
            el(ToggleControl, {
              checked: f.is_enforced != 0,
              onChange: (val) => updateFeature(f.key, { is_enforced: val ? 1 : 0 }),
              disabled: true,
              __nextHasNoMarginBottom: true
            })
          ])
        ])
      ]),
      el(CardBody, { style: { padding: '25px' } }, [
        el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '30px' } }, [
          el('div', null, [
            el('h4', { style: { fontSize: '13px', fontWeight: 700, marginBottom: '15px', color: '#1e293b' } }, __('Security Configuration')),
            implControls.length > 0 ? el(GeneratedInterface, {
              feature: { ...f, generated_schema: { ...schema, controls: implControls } },
              onUpdate: (data) => updateFeature(f.key, { implementation_data: data })
            }) : el('p', { style: { fontSize: '12px', color: '#94a3b8' } }, __('Standard protection rules active.'))
          ]),
          el('div', null, [
            el('h4', { style: { fontSize: '13px', fontWeight: 700, marginBottom: '15px', color: '#1e293b' } }, __('Verification Engine')),
            automControls.length > 0 ? el(GeneratedInterface, {
              feature: { ...f, generated_schema: { ...schema, controls: automControls } },
              hideOpNotes: true,
              hideProtocol: true,
              onUpdate: (data) => updateFeature(f.key, { implementation_data: data })
            }) : el('p', { style: { fontSize: '12px', color: '#94a3b8' } }, __('Automated monitoring active.'))
          ])
        ])
      ]),
    ]);
  };

  const init = () => {
    const container = document.getElementById('vapt-client-root');
    if (container) render(el(ClientDashboard), container);
  };
  if (document.readyState === 'complete') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
