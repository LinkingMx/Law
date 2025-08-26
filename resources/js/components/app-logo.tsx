import { BookText } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {/* Libro como logo principal */}
                <BookText className="size-5 text-sidebar-primary-foreground" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">Documentación Legal</span>
            </div>
        </>
    );
}
