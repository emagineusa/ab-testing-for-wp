import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';

import ControlSettings from './ControlSettings';
import DistributionSettings from './DistributionSettings';

type VariantSettingsProps = {
  control: string;
  variants: ABTestVariant[];
  onControlChange: (variantId: string) => void;
  onUpdateVariants: (variants: ABTestVariant[]) => void;
};

const VariantSettings: React.FC<VariantSettingsProps> = ({
  control, variants, onControlChange, onUpdateVariants,
}) => {
  const onUpdateDistribution = (id: string, newDistribution: number | undefined): void => {
    if (!newDistribution) return;

    const otherVariants = variants.filter((test) => test.id !== id);
    const combinedLeft = otherVariants.reduce((a, b) => a + (b.distribution || 0), 0);
    const rate = combinedLeft !== 0 ? (100 - newDistribution) / combinedLeft : 0;

    onUpdateVariants(variants.map((variant) => {
      if (variant.id === id) {
        return {
          ...variant,
          distribution: newDistribution,
        };
      }

      return {
        ...variant,
        distribution: Math.round(combinedLeft === 0
          ? (100 - newDistribution) / otherVariants.length
          : variant.distribution * rate),
      };
    }));
  };

  return (
    <PanelBody title={__('Variantions', 'ab-testing-for-wp')}>
      <ControlSettings
        value={control}
        variants={variants}
        onChange={onControlChange}
      />
      {variants.map((variant) => (
        <DistributionSettings
          variant={variant}
          onUpdateDistribution={onUpdateDistribution}
        />
      ))}
    </PanelBody>
  );
};

export default VariantSettings;
