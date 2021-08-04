import ABTest from './frontend/ABTest';

function onLoad(): void {
  const testBlocks = Array.from(document.querySelectorAll('.ABTestWrapper'));
  testBlocks.map((block) => new ABTest((block as HTMLElement)));
}

document.addEventListener('DOMContentLoaded', onLoad);
