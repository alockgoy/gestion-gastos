export const Card = ({ children, className = '', hover = false }) => {
  return (
    <div className={`${hover ? 'card-hover' : 'card'} ${className}`}>
      {children}
    </div>
  );
};