import React from 'react';
import { createRoot } from 'react-dom/client';
import {
    ResponsiveContainer, BarChart, Bar, AreaChart, Area, PieChart, Pie, Cell,
    XAxis, YAxis, CartesianGrid, Tooltip, Legend,
} from 'recharts';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const COLORS = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899'];

const peso = (n) =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(n) || 0);
const num = (n) => new Intl.NumberFormat('en-PH').format(Number(n) || 0);

class ErrorBoundary extends React.Component {
    state = { error: null };
    static getDerivedStateFromError(error) { return { error }; }
    render() {
        if (this.state.error) {
            return (
                <div className="rounded-xl bg-rose-50 p-6 text-sm text-rose-700 ring-1 ring-rose-600/10">
                    <p className="font-semibold">Dashboard failed to render</p>
                    <p className="mt-1 font-mono text-xs">{String(this.state.error.message || this.state.error)}</p>
                </div>
            );
        }
        return this.props.children;
    }
}

function StatCard({ label, value, hint, tone = 'indigo' }) {
    const tones = {
        indigo: 'bg-indigo-50 text-indigo-600',
        emerald: 'bg-emerald-50 text-emerald-600',
        amber: 'bg-amber-50 text-amber-600',
        sky: 'bg-sky-50 text-sky-600',
    };
    return (
        <div className="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 shadow-sm">
            <div className="flex items-start justify-between">
                <p className="text-sm font-medium text-gray-500">{label}</p>
                <span className={`h-9 w-9 rounded-lg ${tones[tone]}`} />
            </div>
            <p className="mt-3 text-2xl font-bold tracking-tight text-gray-950">{value}</p>
            <p className="mt-2 text-xs text-gray-400">{hint}</p>
        </div>
    );
}

function Panel({ title, subtitle, children }) {
    return (
        <div className="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 shadow-sm">
            <h3 className="text-base font-semibold text-gray-950">{title}</h3>
            <p className="mb-4 text-sm text-gray-500">{subtitle}</p>
            <div style={{ width: '100%', height: 280 }}>{children}</div>
        </div>
    );
}

function Empty({ text }) {
    return <div className="flex h-full items-center justify-center text-sm text-gray-400">{text}</div>;
}

function Dashboard({ data }) {
    const { stats, status, thisMonth } = data;

    const appsSeries = MONTHS.map((m, i) => ({ month: m, total: Number(data.monthlyApplications?.[i]) || 0 }));
    const subsSeries = MONTHS.map((m, i) => ({ month: m, total: Number(data.monthlySubsidies?.[i]) || 0 }));
    const breakdown = (data.subsidyBreakdown || []).filter((r) => Number(r.total_amount) > 0);

    const statusSeries = [
        { name: 'Pending', value: status.pending },
        { name: 'For verification', value: status.verification },
        { name: 'Approved', value: status.approved },
        { name: 'Rejected', value: status.rejected },
    ].filter((r) => r.value > 0);

    return (
        <div className="space-y-5">
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Total Senior Citizens" value={num(stats.totalSeniorCitizens)} hint="Registered in the system" tone="indigo" />
                <StatCard label="Active Beneficiaries" value={num(stats.activeBeneficiaries)} hint={`${num(stats.approvedBeneficiaries)} approved`} tone="emerald" />
                <StatCard label="Pending Applications" value={num(stats.pendingApplications)} hint="Awaiting review" tone="amber" />
                <StatCard label="Total Subsidy Distributed" value={peso(stats.totalSubsidyDistributed)} hint={`${peso(thisMonth.subsidy)} this month (${num(thisMonth.releases)} releases)`} tone="sky" />
            </div>

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <Panel title="Applications per Month" subtitle="Applications received this year">
                    {appsSeries.some((r) => r.total > 0) ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={appsSeries}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} />
                                <YAxis allowDecimals={false} tickLine={false} axisLine={false} />
                                <Tooltip />
                                <Bar dataKey="total" name="Applications" fill="#4f46e5" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    ) : <Empty text="No applications recorded this year" />}
                </Panel>

                <Panel title="Subsidy Released per Month" subtitle="Amount released this year">
                    {subsSeries.some((r) => r.total > 0) ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={subsSeries}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} />
                                <YAxis tickLine={false} axisLine={false} />
                                <Tooltip formatter={(v) => peso(v)} />
                                <Area type="monotone" dataKey="total" name="Released" stroke="#10b981" fill="#10b98133" />
                            </AreaChart>
                        </ResponsiveContainer>
                    ) : <Empty text="No subsidy releases recorded this year" />}
                </Panel>

                <Panel title="Subsidy Breakdown" subtitle="Total released per program">
                    {breakdown.length ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie data={breakdown} dataKey="total_amount" nameKey="name" innerRadius={55} outerRadius={95} paddingAngle={2}>
                                    {breakdown.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                                </Pie>
                                <Tooltip formatter={(v) => peso(v)} />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    ) : <Empty text="No subsidy data to break down" />}
                </Panel>

                <Panel title="Application Status" subtitle="All applications by status">
                    {statusSeries.length ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie data={statusSeries} dataKey="value" nameKey="name" outerRadius={95}>
                                    {statusSeries.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    ) : <Empty text="No applications yet" />}
                </Panel>
            </div>
        </div>
    );
}

const el = document.getElementById('dashboard-root');
if (el) {
    const data = window.__DASHBOARD_DATA__;
    createRoot(el).render(
        <ErrorBoundary>
            {data ? <Dashboard data={data} /> : (
                <div className="rounded-xl bg-amber-50 p-6 text-sm text-amber-700 ring-1 ring-amber-600/10">
                    window.__DASHBOARD_DATA__ is missing. Check that the data script renders before this bundle loads.
                </div>
            )}
        </ErrorBoundary>
    );
}