import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type Branch } from '@/types';
import { FileText, Clock, AlertTriangle, TrendingUp } from 'lucide-react';

interface BranchStatisticsProps {
    branch: Branch;
}

export function BranchStatistics({ branch }: BranchStatisticsProps) {
    const totalDocuments = branch.documents_count || 0;
    const expiringSoon = branch.expiring_soon_count || 0;
    const expired = branch.expired_count || 0;
    const valid = totalDocuments - expiringSoon - expired;

    const stats = [
        {
            title: 'Total Documentos',
            value: totalDocuments,
            icon: FileText,
            iconClass: 'icon-bg-info',
            cardClass: 'stat-card-info',
        },
        {
            title: 'Documentos Vigentes',
            value: valid,
            icon: TrendingUp,
            iconClass: 'icon-bg-success',
            cardClass: 'stat-card-success',
        },
        {
            title: 'Por Vencer (30 días)',
            value: expiringSoon,
            icon: Clock,
            iconClass: 'icon-bg-warning',
            cardClass: 'stat-card-warning',
        },
        {
            title: 'Vencidos',
            value: expired,
            icon: AlertTriangle,
            iconClass: 'icon-bg-error',
            cardClass: 'stat-card-error',
        },
    ];

    return (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
            {stats.map((stat) => (
                <Card key={stat.title} className={`relative overflow-hidden ${stat.cardClass}`}>
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
    );
}