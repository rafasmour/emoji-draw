import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';


export default function Preferences() {
    return (
        <AppLayout>
            <SettingsLayout>
                <HeadingSmall title={"Preferences"} />
            </SettingsLayout>
        </AppLayout>
    )
}
