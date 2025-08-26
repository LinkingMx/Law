import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Branch, type Document } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2, FileText, Clock, AlertTriangle, TrendingUp, Calendar } from 'lucide-react';

interface DashboardProps {
    statistics: {
        total_branches: number;
        total_documents: number;
        documents_vigent: number;
        documents_expiring_soon: number;
        documents_expired: number;
    };
    branchSummary: Array<{
        id: number;
        name: string;
        address: string;
        documents_count: number;
        expiring_soon_count: number;
        expired_count: number;
    }>;
    recentDocuments: Document[];
}

export default function Dashboard({ statistics, branchSummary, recentDocuments }: DashboardProps) {
    const { t } = useTranslations();
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('Dashboard'),
            href: '/dashboard',
        },
    ];
    const mainStats = [
        {
            title: t('Total Branches'),
            value: statistics.total_branches,
            icon: Building2,
            iconClass: 'icon-bg-primary',
            cardClass: 'stat-card',
        },
        {
            title: t('Total Documents'),
            value: statistics.total_documents,
            icon: FileText,
            iconClass: 'icon-bg-info',
            cardClass: 'stat-card-info',
        },
        {
            title: t('Documents Vigent'),
            value: statistics.documents_vigent,
            icon: TrendingUp,
            iconClass: 'icon-bg-success',
            cardClass: 'stat-card-success',
        },
        {
            title: t('Expiring Soon (30 days)'),
            value: statistics.documents_expiring_soon,
            icon: Clock,
            iconClass: 'icon-bg-warning',
            cardClass: 'stat-card-warning',
        },
        {
            title: t('Expired'),
            value: statistics.documents_expired,
            icon: AlertTriangle,
            iconClass: 'icon-bg-error',
            cardClass: 'stat-card-error',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Dashboard')} />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6 overflow-auto">
                {/* Welcome Section */}
                <div className="space-y-1">
                    <h1 className="text-2xl lg:text-3xl font-bold">{t('Dashboard')}</h1>
                    <p className="text-muted-foreground">{t('General overview of documents and branches')}</p>
                </div>

                {/* Main Statistics */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    {mainStats.map((stat) => (
                        <Card key={stat.title} className={stat.cardClass}>
                            <CardHeader className="pb-2">
                                <div className="flex items-center justify-between">
                                    <div className={stat.iconClass}>
                                        <stat.icon className="h-4 w-4" />
                                    </div>
                                    <Badge variant="outline" className="text-xl font-bold">
                                        {stat.value}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-0">
                                <CardTitle className="text-xs lg:text-sm font-medium text-muted-foreground">
                                    {stat.title}
                                </CardTitle>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Branches Summary & Recent Documents */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Branches Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Resumen de Sucursales
                            </CardTitle>
                            <CardDescription>
                                Estado de documentos por sucursal
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {branchSummary.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">
                                        No tienes sucursales asignadas
                                    </p>
                                ) : (
                                    branchSummary.map((branch) => (
                                        <Link
                                            key={branch.id}
                                            href={`/documents?branch=${branch.id}`}
                                            className="block p-3 rounded-lg border hover:bg-muted/50 transition-colors"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1 min-w-0">
                                                    <h4 className="font-medium text-sm truncate">{branch.name}</h4>
                                                    <p className="text-xs text-muted-foreground truncate">
                                                        {branch.address}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-1 flex-shrink-0 ml-2">
                                                    <Badge variant="outline" className="text-xs">
                                                        {branch.documents_count}
                                                    </Badge>
                                                    {branch.expiring_soon_count > 0 && (
                                                        <Badge className="text-xs badge-warning">
                                                            {branch.expiring_soon_count}
                                                        </Badge>
                                                    )}
                                                    {branch.expired_count > 0 && (
                                                        <Badge className="text-xs badge-error">
                                                            {branch.expired_count}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </Link>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Documents */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Documentos Recientes
                            </CardTitle>
                            <CardDescription>
                                Últimos documentos agregados
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {recentDocuments.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">
                                        No hay documentos recientes
                                    </p>
                                ) : (
                                    recentDocuments.map((document) => (
                                        <div
                                            key={document.id}
                                            className="p-3 rounded-lg border"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1 min-w-0">
                                                    <h4 className="font-medium text-sm truncate">{document.name}</h4>
                                                    <p className="text-xs text-muted-foreground">
                                                        {document.branch?.name} • {document.category?.name}
                                                    </p>
                                                    {document.expire_date && (
                                                        <div className="mt-1">
                                                            <Badge variant="outline" className="text-xs">
                                                                <Calendar className="h-3 w-3 mr-1" />
                                                                {new Date(document.expire_date).toLocaleDateString('es-ES')}
                                                            </Badge>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
